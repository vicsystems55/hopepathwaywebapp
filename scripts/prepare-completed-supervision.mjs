import assert from 'node:assert/strict'
import { createHash } from 'node:crypto'
import { mkdir, readFile, stat, writeFile } from 'node:fs/promises'
import { basename, dirname, resolve } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const TABLES = new Set(['staff_records', 'staff_supervision_schedules', 'supervision_answers', 'supervision_questions'])

// This is a data parser. Statements from the backup are never executed.
export function splitSqlStatements(sql) {
  const statements = []
  let current = ''
  let quote = null
  for (let index = 0; index < sql.length; index++) {
    const character = sql[index]
    if (quote) {
      current += character
      if (character === '\\' && quote !== '`') {
        if (++index >= sql.length) throw new Error('Unterminated SQL escape')
        current += sql[index]
      } else if (character === quote) {
        if (sql[index + 1] === quote) current += sql[++index]
        else quote = null
      }
      continue
    }
    if (character === "'" || character === '"' || character === '`') {
      quote = character
      current += character
    } else if (character === '#' || (character === '-' && sql[index + 1] === '-' && /\s/.test(sql[index + 2] || ''))) {
      while (index < sql.length && sql[index] !== '\n') index++
      current += '\n'
    } else if (character === '/' && sql[index + 1] === '*') {
      const end = sql.indexOf('*/', index + 2)
      if (end < 0) throw new Error('Unterminated SQL comment')
      index = end + 1
      current += ' '
    } else if (character === ';') {
      if (current.trim()) statements.push(current.trim())
      current = ''
    } else current += character
  }
  if (quote) throw new Error('Unterminated SQL string or identifier')
  if (current.trim()) statements.push(current.trim())
  return statements
}

export function parseValues(payload) {
  const rows = []
  let position = 0
  const whitespace = () => { while (/\s/.test(payload[position] || '') && position < payload.length) position++ }
  const quotedValue = () => {
    const quote = payload[position++]
    let value = ''
    while (position < payload.length) {
      const character = payload[position++]
      if (character === '\\') {
        if (position >= payload.length) throw new Error('Unterminated value escape')
        const escaped = payload[position++]
        const escapes = { '0': '\0', b: '\b', n: '\n', r: '\r', t: '\t', Z: '\x1a' }
        value += escaped in escapes ? escapes[escaped] : ((escaped === '%' || escaped === '_') ? `\\${escaped}` : escaped)
      } else if (character === quote) {
        if (payload[position] === quote) { value += quote; position++ }
        else return value
      } else value += character
    }
    throw new Error('Unterminated quoted value')
  }
  while (position < payload.length) {
    whitespace()
    if (payload[position++] !== '(') throw new Error('Expected INSERT row')
    const row = []
    let closed = false
    while (position < payload.length) {
      whitespace()
      if (payload[position] === "'" || payload[position] === '"') row.push(quotedValue())
      else {
        const begin = position
        while (position < payload.length && payload[position] !== ',' && payload[position] !== ')') position++
        const value = payload.slice(begin, position).trim()
        if (/^NULL$/i.test(value)) row.push(null)
        else if (/^-?\d+(?:\.\d+)?(?:e[+-]?\d+)?$/i.test(value)) row.push(Number(value))
        else throw new Error('Unsupported nonliteral SQL value')
      }
      whitespace()
      if (payload[position] === ')') { position++; closed = true; break }
      if (payload[position++] !== ',') throw new Error('Expected value separator')
    }
    if (!closed) throw new Error('Unterminated INSERT row')
    rows.push(row)
    whitespace()
    if (position === payload.length) break
    if (payload[position++] !== ',') throw new Error('Expected row separator')
  }
  return rows
}

export function parseBackup(sql) {
  const tables = Object.fromEntries([...TABLES].map(table => [table, []]))
  for (const statement of splitSqlStatements(sql)) {
    const insert = statement.match(/^INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?\s*\(([^)]+)\)\s*VALUES\s*/i)
    if (!insert || !TABLES.has(insert[1])) continue
    const columns = insert[2].split(',').map(column => column.trim().replace(/^`|`$/g, ''))
    for (const values of parseValues(statement.slice(insert[0].length))) {
      if (values.length !== columns.length) throw new Error(`Column count mismatch in ${insert[1]}`)
      tables[insert[1]].push(Object.fromEntries(columns.map((column, index) => [column, values[index]])))
    }
  }
  return tables
}

const dateRange = values => {
  const dates = values.filter(value => typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)).sort()
  return { first: dates[0] || null, last: dates.at(-1) || null }
}

function indexed(rows, label) {
  const index = new Map()
  for (const row of rows) {
    if (!Number.isSafeInteger(row.id) || index.has(row.id)) throw new Error(`Missing or repeated primary ID in ${label}`)
    index.set(row.id, row)
  }
  return index
}

const questionSnapshot = question => ({
  text: question.question,
  question_type: question.question_type,
  options: { a: question.option_a, b: question.option_b, d: question.option_d, e: question.option_e },
  created_at: question.created_at,
  updated_at: question.updated_at,
})

export function buildArchive(tables, source) {
  const staff = indexed(tables.staff_records, 'staff_records')
  const questions = indexed(tables.supervision_questions, 'supervision_questions')
  indexed(tables.staff_supervision_schedules, 'staff_supervision_schedules')
  indexed(tables.supervision_answers, 'supervision_answers')
  const answersBySchedule = new Map()
  for (const answer of tables.supervision_answers) {
    if (!answersBySchedule.has(answer.staff_supervision_schedule_id)) answersBySchedule.set(answer.staff_supervision_schedule_id, [])
    answersBySchedule.get(answer.staff_supervision_schedule_id).push(answer)
  }
  const completedSchedules = tables.staff_supervision_schedules.filter(schedule => String(schedule.status).toLowerCase() === 'completed')
  const selectedSchedules = completedSchedules.filter(schedule => (answersBySchedule.get(schedule.id) || []).length)
  const missingAnswers = completedSchedules.filter(schedule => !(answersBySchedule.get(schedule.id) || []).length).map(schedule => schedule.id)
  if (missingAnswers.length) throw new Error(`Completed sessions have no answers: ${missingAnswers.join(', ')}`)

  const usedStaff = new Set()
  const usedQuestions = new Set()
  const sessions = selectedSchedules.map(schedule => {
    if (!staff.has(schedule.staff_record_id)) throw new Error(`Completed session ${schedule.id} references missing staff`)
    usedStaff.add(schedule.staff_record_id)
    const answers = [...answersBySchedule.get(schedule.id)].sort((left, right) => left.id - right.id)
    const answersByQuestion = new Map()
    for (const answer of answers) {
      if (!questions.has(answer.supervision_question_id)) throw new Error(`Answer ${answer.id} references missing question`)
      usedQuestions.add(answer.supervision_question_id)
      if (!answersByQuestion.has(answer.supervision_question_id)) answersByQuestion.set(answer.supervision_question_id, [])
      answersByQuestion.get(answer.supervision_question_id).push(answer.id)
    }
    const duplicateGroups = [...answersByQuestion].filter(([, ids]) => ids.length > 1)
      .map(([questionId, answerIds]) => ({ legacy_question_id: questionId, answer_ids: answerIds }))
    const createdRange = dateRange(answers.map(answer => answer.created_at))
    const updatedRange = dateRange(answers.map(answer => answer.updated_at))
    const result = {
      legacy_session_id: schedule.id,
      legacy_staff_id: schedule.staff_record_id,
      status: schedule.status,
      scheduled_date: schedule.next_supervision_date,
      created_at: schedule.created_at,
      updated_at: schedule.updated_at,
      answer_dates: { first_saved_at: createdRange.first, last_saved_at: createdRange.last, last_updated_at: updatedRange.last },
      distinct_question_count: answersByQuestion.size,
      duplicate_groups: duplicateGroups,
      question_snapshot_uncertain: false,
      answers: answers.map(answer => {
        const question = questions.get(answer.supervision_question_id)
        const questionTimestamp = [question.created_at, question.updated_at].filter(Boolean).sort().at(-1)
        const postdatesAnswer = Boolean(questionTimestamp && answer.created_at && questionTimestamp > answer.created_at)
        return {
          legacy_answer_id: answer.id,
          legacy_question_id: answer.supervision_question_id,
          text: answer.answer,
          question_snapshot: questionSnapshot(question),
          created_at: answer.created_at,
          updated_at: answer.updated_at,
          status: answer.status,
          duplicate_question_answer: answersByQuestion.get(answer.supervision_question_id).length > 1,
          question_snapshot_postdates_answer: postdatesAnswer,
        }
      }),
    }
    result.question_snapshot_uncertain = result.answers.some(answer => answer.question_snapshot_postdates_answer)
    return result
  }).sort((left, right) => left.legacy_staff_id - right.legacy_staff_id || left.legacy_session_id - right.legacy_session_id)

  const staffSnapshots = [...usedStaff].sort((left, right) => left - right).map(id => ({
    legacy_staff_id: id,
    fullname: staff.get(id).fullname,
    staff_id: staff.get(id).staff_id,
    session_ids: sessions.filter(session => session.legacy_staff_id === id).map(session => session.legacy_session_id),
  }))
  const questionSnapshots = [...usedQuestions].sort((left, right) => left - right).map(id => ({ legacy_question_id: id, ...questionSnapshot(questions.get(id)) }))
  const counts = {
    staff: staffSnapshots.length,
    completed_sessions: sessions.length,
    answer_rows: sessions.reduce((total, session) => total + session.answers.length, 0),
    questions: questionSnapshots.length,
    duplicate_answer_groups: sessions.reduce((total, session) => total + session.duplicate_groups.length, 0),
    extra_duplicate_rows: sessions.reduce((total, session) => total + session.duplicate_groups.reduce((count, group) => count + group.answer_ids.length - 1, 0), 0),
    sessions_with_question_snapshot_uncertainty: sessions.filter(session => session.question_snapshot_uncertain).length,
    answers_with_question_snapshot_uncertainty: sessions.reduce((total, session) => total + session.answers.filter(answer => answer.question_snapshot_postdates_answer).length, 0),
  }
  const limitations = [
    'Scheduled dates are source schedule values, not verified supervision completion dates.',
    'Question wording is the wording present in this backup, not a proven snapshot at the time an answer was submitted.',
    'Duplicate answers have been preserved as separate records; no preferred or final version is inferred.',
    'Source records do not establish supervisor identity, signatures, sign-off or original PDF attachments.',
    'Legacy numeric IDs refer only to this backup and must not be used to overwrite or automatically link live records.',
    'Staff mapping to current records requires review; this extraction does not establish live staff identities.',
  ]
  return { schema_version: 1, source, counts, limitations, staff: staffSnapshots, questions: questionSnapshots, sessions }
}

export function selfTest() {
  assert.deepEqual(parseValues("(1, 'O\\'Brien; (quoted)', NULL, 'line\\nnext', 'it''s'), (2, '\\\\path', 'NULL', -3, 'comma,value')"), [
    [1, "O'Brien; (quoted)", null, 'line\nnext', "it's"],
    [2, '\\path', 'NULL', -3, 'comma,value'],
  ])
  const sql = "-- ignored; comment\nINSERT INTO `supervision_questions` (`id`,`question`) VALUES (1, 'semi; -- literal \\' value'); /* ignore ; */ INSERT INTO `other` (`id`) VALUES (2);"
  assert.equal(splitSqlStatements(sql).length, 2)
  assert.equal(parseBackup(sql).supervision_questions[0].question, "semi; -- literal ' value")
  assert.throws(() => parseValues("(1, NOW())"), /Unsupported/)
  assert.throws(() => parseValues("(1,'unterminated)"), /Unterminated/)
  assert.throws(() => parseValues('(1, 2'), /Unterminated|Expected/)
}

export async function prepareArchive(inputPath, outputDirectory) {
  selfTest()
  const input = resolve(inputPath)
  const output = resolve(outputDirectory)
  // The backup must never become a generated output target.
  if (input === resolve(output, 'archive.json') || input === resolve(output, 'manifest.json')) throw new Error('Input/output paths overlap')
  const raw = await readFile(input)
  const sql = raw.toString('utf8')
  const source = {
    file_name: basename(input),
    sha256: createHash('sha256').update(raw).digest('hex'),
    backup_database: sql.match(/^-- Database: `([^`]+)`/m)?.[1] || null,
    exported_at_label: sql.match(/^-- Generation Time: (.+)$/m)?.[1]?.trim() || null,
  }
  const archive = buildArchive(parseBackup(sql), source)
  if (source.file_name === 'sep11-vicsrfjf_hpw.sql') {
    assert.equal(archive.counts.staff, 5, 'Unexpected staff count; review extraction')
    assert.equal(archive.counts.completed_sessions, 29, 'Unexpected completed session count; review extraction')
    assert.equal(archive.counts.answer_rows, 408, 'Unexpected answer count; review extraction')
    assert.equal(archive.counts.questions, 14, 'Unexpected question count; review extraction')
    assert.equal(archive.counts.duplicate_answer_groups, 2)
    assert.equal(archive.counts.extra_duplicate_rows, 3)
  }
  const manifest = {
    schema_version: archive.schema_version,
    source,
    counts: archive.counts,
    answer_dates: dateRange(archive.sessions.flatMap(session => session.answers.map(answer => answer.created_at))),
    scope: 'Only source staff with completed supervision sessions and their completed-session answers',
    excluded: ['Pending or unanswered schedules', 'Unrelated staff records', 'Staff contact, address, birth and identity-document details', 'Users, credentials, tokens, residents and other backup tables'],
    limitations: archive.limitations,
  }
  await mkdir(output, { recursive: true })
  for (const [name, data] of [['archive.json', archive], ['manifest.json', manifest]]) {
    const target = resolve(output, name)
    try {
      await stat(target)
      const existing = JSON.parse(await readFile(target, 'utf8'))
      if (existing.source?.sha256 !== source.sha256) throw new Error(`Refusing to overwrite an archive from a different backup: ${name}`)
      if (JSON.stringify(existing) !== JSON.stringify(data)) throw new Error(`Refusing to overwrite changed archive data: ${name}`)
    } catch (error) {
      if (error.code !== 'ENOENT') throw error
      await writeFile(target, `${JSON.stringify(data, null, 2)}\n`, { encoding: 'utf8', flag: 'wx' })
    }
  }
  return { output_directory: output, source_sha256: source.sha256, counts: archive.counts, parser_self_test: 'passed' }
}

if (process.argv[1] && pathToFileURL(resolve(process.argv[1])).href === import.meta.url) {
  const scriptDirectory = dirname(fileURLToPath(import.meta.url))
  const [inputPath = resolve(scriptDirectory, '../../sep11-vicsrfjf_hpw.sql'), outputDirectory = resolve(scriptDirectory, '../../supervision-archive/sep11-2024-completed')] = process.argv.slice(2)
  try { console.log(JSON.stringify(await prepareArchive(inputPath, outputDirectory), null, 2)) }
  catch (error) { console.error(`Archive preparation failed: ${error.message}`); process.exitCode = 1 }
}
