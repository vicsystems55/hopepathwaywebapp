import test from 'node:test'
import assert from 'node:assert/strict'
import { createHash } from 'node:crypto'
import vm from 'node:vm'
import { renderArchive } from './render-supervision-archive.mjs'

const fixture = () => ({
  source: { file_name: 'synthetic.sql', exported_at_label: 'Example backup', sha256: 'synthetic-hash' },
  counts: { answer_rows: 3 },
  staff: [
    { legacy_staff_id: 1, fullname: 'Synthetic Staff One', staff_id: 'S-1', session_ids: [10, 11] },
    { legacy_staff_id: 2, fullname: 'Synthetic Staff Two', staff_id: 'S-2', session_ids: [12] },
  ],
  sessions: [10, 11, 12].map(id => ({
    legacy_session_id: id, legacy_staff_id: id === 12 ? 2 : 1, status: 'completed',
    scheduled_date: '2023-12-01', answer_dates: { first_saved_at: '2023-12-05 12:00:00', last_saved_at: '2023-12-05 12:00:00' },
    duplicate_groups: [],
    answers: [{ legacy_answer_id: id, legacy_question_id: 1, text: `Response ${id}`, created_at: '2023-12-05 12:00:00', question_snapshot: { text: 'Synthetic question' } }],
  })),
})

// A small DOM model exercises navigation and text insertion without using HR data.
class Element {
  constructor(tag) { this.tag = tag; this.children = []; this.textContent = ''; this.attributes = {}; this.listeners = {}; this.value = '' }
  append(...items) { this.children.push(...items) }
  replaceChildren(...items) { this.children = items }
  setAttribute(key, value) { this.attributes[key] = value }
  addEventListener(name, fn) { this.listeners[name] = fn }
}
function mount(archive) {
  const html = renderArchive(archive)
  const embedded = html.match(/<script id="archive-data" type="application\/json">([\s\S]*?)<\/script>/)[1]
  const code = html.match(/<script>([\s\S]*?)<\/script>/)[1]
  const elements = new Map()
  const document = {
    createElement: tag => new Element(tag),
    getElementById: id => {
      if (!elements.has(id)) elements.set(id, new Element('div'))
      return elements.get(id)
    },
  }
  document.getElementById('archive-data').textContent = embedded
  let printed = false
  vm.runInNewContext(code, { document, window: { print: () => { printed = true } } })
  return { html, embedded, code, get: id => document.getElementById(id), printed: () => printed }
}

test('viewer selects only the chosen staff sessions and supports search and print', () => {
  const view = mount(fixture())
  assert.equal(view.get('staff-list').children.length, 2)
  assert.equal(view.get('session-list').children.length, 2)
  assert.equal(view.get('staff-heading').textContent, 'Synthetic Staff One')
  view.get('staff-list').children[1].listeners.click()
  assert.equal(view.get('staff-heading').textContent, 'Synthetic Staff Two')
  assert.equal(view.get('session-list').children.length, 1)
  assert.equal(view.get('session-list').children[0].children[0].textContent, 'Session #12')
  view.get('search').value = 's-2'
  view.get('search').listeners.input()
  assert.equal(view.get('staff-list').children.length, 1)
  view.get('print').listeners.click()
  assert.equal(view.printed(), true)
})

test('stored HTML is inserted as text and the inline code hash matches its CSP', () => {
  const data = fixture()
  const payload = '</script><script>alert("unsafe")</script><img src=x onerror=alert(1)>'
  data.staff[0].fullname = payload
  data.sessions[0].answers[0].text = payload
  const view = mount(data)
  assert.equal(view.html.includes(payload), false)
  assert.equal(JSON.parse(view.embedded).sessions[0].answers[0].text, payload)
  assert.equal(view.get('staff-list').children[0].children[0].textContent, payload)
  const hash = createHash('sha256').update(view.code).digest('base64')
  assert.ok(view.html.includes(`script-src 'sha256-${hash}'`))
  assert.equal(view.code.includes('innerHTML'), false)
})

test('duplicate saved responses remain visible and pending sessions are rejected', () => {
  const data = fixture()
  data.sessions[0].duplicate_groups = [{ legacy_question_id: 1, answer_ids: [10, 100] }]
  data.sessions[0].answers.push({ ...data.sessions[0].answers[0], legacy_answer_id: 100, text: 'Other response' })
  const view = mount(data)
  view.get('session-list').children.find(button => button.children[0].textContent === 'Session #10').listeners.click()
  const stringify = element => element.textContent + element.children.map(stringify).join(' ')
  const rendered = stringify(view.get('session-detail'))
  assert.ok(rendered.includes('Response 10'))
  assert.ok(rendered.includes('Other response'))
  assert.ok(rendered.includes('Saved response 1 of 2'))
  assert.ok(rendered.includes('Saved response 2 of 2'))
  data.sessions[0].status = 'active'
  assert.throws(() => renderArchive(data), /Only completed/)
})
