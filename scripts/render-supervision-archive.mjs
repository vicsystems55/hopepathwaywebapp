import { readFile, mkdir, writeFile } from 'node:fs/promises'
import { resolve, dirname } from 'node:path'
import { createHash } from 'node:crypto'
import { pathToFileURL } from 'node:url'

export function renderArchive(archive) {
  if (!Array.isArray(archive.staff) || !Array.isArray(archive.sessions)) throw new Error('Invalid supervision archive.')
  if (archive.sessions.some(session => session.status !== 'completed')) throw new Error('Only completed sessions may be rendered.')
  const json = JSON.stringify(archive).replace(/</g, '\\u003c').replace(/\u2028/g, '\\u2028').replace(/\u2029/g, '\\u2029')
  const code = String.raw`
const archive = JSON.parse(document.getElementById('archive-data').textContent);
const staffList = document.getElementById('staff-list');
const sessionList = document.getElementById('session-list');
const detail = document.getElementById('session-detail');
let selectedStaffId = archive.staff[0]?.legacy_staff_id;
let selectedSessionId = null;
const byId = (a,b) => String(a) === String(b);
const node = (tag, text, className) => {
  const el = document.createElement(tag);
  if (text !== undefined && text !== null) el.textContent = String(text);
  if (className) el.className = className;
  return el;
};
const date = value => {
  if (!value) return 'Not recorded';
  const parsed = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
};
const timestamp = value => value ? String(value) + ' (source timestamp)' : 'Not recorded';
const stat = (label, value) => { const box = node('div', null, 'stat'); box.append(node('strong',value), node('span',label)); return box; };
document.getElementById('stats').append(stat('Staff members',archive.staff.length),stat('Completed sessions',archive.sessions.length),stat('Saved answer rows',archive.counts.answer_rows));
document.getElementById('source').textContent = 'Source: ' + archive.source.file_name + ' | Export: ' + archive.source.exported_at_label;
document.getElementById('source-hash').textContent = 'Source SHA-256: ' + archive.source.sha256;
function renderStaff() {
  staffList.replaceChildren();
  const term = document.getElementById('search').value.trim().toLowerCase();
  const visible = archive.staff.filter(person => [person.fullname,person.staff_id].some(value => String(value || '').toLowerCase().includes(term)));
  for (const person of visible) {
    const button = node('button',null,'staff-button' + (byId(person.legacy_staff_id,selectedStaffId) ? ' selected' : ''));
    button.type = 'button';
    button.setAttribute('aria-pressed',String(byId(person.legacy_staff_id,selectedStaffId)));
    button.append(node('strong',person.fullname),node('span',(person.staff_id || 'Legacy staff #' + person.legacy_staff_id) + ' · ' + person.session_ids.length + ' completed sessions'));
    button.addEventListener('click',() => { selectedStaffId = person.legacy_staff_id; selectedSessionId = null; renderStaff(); renderSessions(); });
    staffList.append(button);
  }
  if (!visible.length) staffList.append(node('p','No matching staff.','muted'));
}
function renderSessions() {
  const person = archive.staff.find(item => byId(item.legacy_staff_id,selectedStaffId));
  document.getElementById('staff-heading').textContent = person?.fullname || 'Completed sessions';
  const sessions = archive.sessions.filter(item => byId(item.legacy_staff_id,selectedStaffId)).sort((a,b) => String(b.answer_dates.last_saved_at || '').localeCompare(String(a.answer_dates.last_saved_at || '')) || b.legacy_session_id-a.legacy_session_id);
  if (!sessions.some(item => byId(item.legacy_session_id,selectedSessionId))) selectedSessionId = sessions[0]?.legacy_session_id;
  sessionList.replaceChildren();
  for (const session of sessions) {
    const button = node('button',null,'session-button' + (byId(session.legacy_session_id,selectedSessionId) ? ' selected' : ''));
    button.type='button';
    button.setAttribute('aria-pressed',String(byId(session.legacy_session_id,selectedSessionId)));
    button.append(node('strong','Session #' + session.legacy_session_id),node('span','Scheduled: ' + date(session.scheduled_date)),node('small',session.answers.length + ' answers · Last saved ' + date(session.answer_dates.last_saved_at)));
    if(session.duplicate_groups.length) button.append(node('small','Duplicate responses retained','duplicate-label'));
    button.addEventListener('click',()=>{selectedSessionId=session.legacy_session_id;renderSessions();});
    sessionList.append(button);
  }
  renderDetail();
}
function renderDetail() {
  detail.replaceChildren();
  const session=archive.sessions.find(item=>byId(item.legacy_session_id,selectedSessionId));
  if(!session) { detail.append(node('p','No completed sessions available.')); return; }
  const person=archive.staff.find(item=>byId(item.legacy_staff_id,session.legacy_staff_id));
  const top = node('header',null,'session-header');
  top.append(node('p','HOPE PATHWAY · INDEPENDENT LIVING','eyebrow'),node('h2',person.fullname + ' — Supervision #' + session.legacy_session_id),node('span','Completed in source backup','badge'));
  const meta=node('dl',null,'metadata');
  for(const [label,value] of [
    ['Staff reference',person.staff_id || 'Not recorded'],['Legacy staff ID',person.legacy_staff_id],
    ['Scheduled date',date(session.scheduled_date)],['First answer saved',timestamp(session.answer_dates.first_saved_at)],
    ['Last answer saved',timestamp(session.answer_dates.last_saved_at)],['Supervisor and signature','Not recorded in source database']
  ]) { const box=node('div'); box.append(node('dt',label),node('dd',value));meta.append(box); }
  top.append(meta);
  detail.append(top,node('p','These are historical records as stored in the backup. The scheduled date is not a verified meeting or completion date. Question wording is taken from the backup question bank; wording at the time of the meeting cannot be independently confirmed.','notice'));
  if(session.duplicate_groups.length) detail.append(node('p','Review note: this session includes multiple saved answers to the same question. Every saved version is shown below; none has been selected as authoritative.','notice warning'));
  const grouped=new Map();
  for(const answer of session.answers) { const key=String(answer.legacy_question_id);if(!grouped.has(key)) grouped.set(key,[]); grouped.get(key).push(answer); }
  let number=0;
  for(const answers of grouped.values()) {
    number++;
    const section=node('section',null,'question');
    section.append(node('p','QUESTION ' + number + ' · SOURCE ID ' + answers[0].legacy_question_id,'question-label'),node('h3',answers[0].question_snapshot.text || 'Question text unavailable'));
    for(const [index,answer] of answers.entries()) {
      const answerBox=node('div',null,'answer');
      if(answers.length>1) answerBox.append(node('p','Saved response ' + (index+1) + ' of ' + answers.length + ' · Answer #' + answer.legacy_answer_id,'version-label'));
      answerBox.append(node('div',answer.text ?? '[No answer text recorded]','answer-text'));
      answerBox.append(node('p','Answer #' + answer.legacy_answer_id + ' · Saved ' + timestamp(answer.created_at),'answer-date'));
      section.append(answerBox);
    }
    detail.append(section);
  }
  detail.append(node('footer','Historical source: ' + archive.source.file_name + ' · Legacy session #' + session.legacy_session_id,'record-footer'));
}
document.getElementById('search').addEventListener('input',renderStaff);
document.getElementById('print').addEventListener('click',()=>window.print());
renderStaff();renderSessions();
`
  const cspHash = createHash('sha256').update(code).digest('base64')
  return `<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; script-src 'sha256-${cspHash}'; base-uri 'none'; form-action 'none'">
<title>Hope Pathway Completed Supervision Archive</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f6fa;color:#102a43;font:15px/1.6 system-ui,-apple-system,Segoe UI,sans-serif}.page-header{padding:28px max(24px,calc((100% - 1500px)/2));background:#fff;border-bottom:1px solid #dce6ef}.eyebrow{color:#c74b00;font-size:11px;font-weight:800;letter-spacing:.16em;margin:0 0 6px}h1{font-size:30px;line-height:1.2;margin:0 0 12px}h2{font-size:22px;line-height:1.35;margin:0 0 12px}h3{font-size:15px;line-height:1.6;margin:0;white-space:pre-wrap}p{margin:0 0 10px}.muted{color:#64748b}.privacy{display:inline-block;border-radius:20px;padding:4px 12px;background:#fff4e8;color:#a34200;font-size:12px;font-weight:750}.summary{max-width:1548px;margin:auto;padding:24px}.stats{display:flex;gap:14px;margin-bottom:22px}.stat{flex:1;padding:15px 20px;background:#fff;border:1px solid #dce6ef;border-radius:14px}.stat strong,.stat span{display:block}.stat strong{font-size:26px}.stat span{font-size:12px;color:#64748b}.layout{display:grid;grid-template-columns:260px minmax(0,1fr);gap:22px}.sidebar,.workspace{min-width:0}label{display:block;font-size:12px;font-weight:700;margin-bottom:7px}input{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:11px;font:inherit;background:#fff;color:inherit}.staff-list{display:grid;gap:10px;margin-top:14px}button{font:inherit;cursor:pointer;text-align:left}.staff-button{display:block;width:100%;padding:15px;border:1px solid #dce6ef;border-radius:12px;background:#fff;color:inherit}.staff-button strong,.staff-button span{display:block}.staff-button span{margin-top:6px;font-size:12px;color:#64748b}.staff-button.selected{border-color:#087fc3;background:#eaf7ff;box-shadow:inset 4px 0 #087fc3}.workspace-toolbar{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:14px}.workspace-toolbar h2{margin:0}.print-button{flex-shrink:0;border:0;background:#087fc3;color:#fff;border-radius:10px;padding:10px 16px;font-size:13px;font-weight:700}.session-list{display:flex;gap:10px;overflow:auto;padding:3px 3px 15px;max-width:100%}.session-button{min-width:195px;flex:0 0 auto;background:#fff;border:1px solid #dce6ef;border-radius:12px;color:inherit;padding:12px 15px}.session-button strong,.session-button span,.session-button small{display:block}.session-button span,.session-button small{font-size:11px;color:#64748b}.session-button.selected{border-color:#087fc3;background:#eaf7ff}.session-button small.duplicate-label{color:#a34200;font-weight:700}.session-detail{padding:28px;border:1px solid #dce6ef;border-radius:16px;background:#fff;overflow-wrap:anywhere}.badge{display:inline-block;background:#e5f7ef;color:#126044;border-radius:20px;padding:4px 10px;font-size:11px;font-weight:700}.metadata{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:20px 0}.metadata div{padding:10px 12px;background:#f6f8fb;border-radius:8px}.metadata dt{font-size:11px;color:#64748b}.metadata dd{font-size:12px;font-weight:600;margin:3px 0 0}.notice{font-size:12px;line-height:1.7;background:#eef6fc;border-radius:10px;padding:13px 15px;margin:0 0 20px}.warning{background:#fff4e8;color:#8a3c00}.question{border-top:1px solid #e2e8f0;padding:20px 0 4px}.question-label{font-size:10px;font-weight:800;color:#087fc3;letter-spacing:.1em;margin-bottom:7px}.answer{margin:12px 0 16px;background:#f8fafc;border-left:3px solid #bdd8eb;padding:14px 16px}.answer-text{font-size:14px;white-space:pre-wrap}.version-label{font-size:11px;font-weight:750;color:#a34200}.answer-date,.record-footer{font-size:10px;color:#64748b;margin-top:12px}.source-notes{margin:22px 0 0;font-size:11px;color:#64748b;overflow-wrap:anywhere}.source-notes p{margin:0 0 5px}button:focus-visible,input:focus-visible{outline:3px solid #f55b00;outline-offset:3px}noscript{display:block;padding:30px;color:#a34200}
@media(max-width:800px){.layout{grid-template-columns:1fr}.staff-list{grid-template-columns:repeat(2,minmax(0,1fr))}.summary{padding:16px}.page-header{padding:22px 16px}.session-detail{padding:18px}.workspace-toolbar{align-items:flex-start}.metadata{grid-template-columns:1fr}.stats{gap:8px}.stat{padding:12px}.stat strong{font-size:22px}}
@media print{@page{size:A4;margin:16mm}body{background:white;color:#000;font-size:11pt}.page-header,.stats,.sidebar,.workspace-toolbar,.session-list,.source-notes{display:none!important}.summary{padding:0;max-width:none}.layout{display:block}.session-detail{border:0;padding:0;border-radius:0}.metadata{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.metadata div,.notice,.answer{background:none}.question h3,.question-label{break-after:avoid}.answer{border-left:2px solid #aaa}.record-footer{margin-top:20px}h2{font-size:17pt}.answer-text{font-size:10.5pt}.answer-date{font-size:8pt}.question{padding-top:13px}.session-header,.notice{break-inside:avoid}}
</style></head><body>
<header class="page-header"><p class="eyebrow">HOPE PATHWAY · INDEPENDENT LIVING</p><h1>Completed supervision archive</h1><p class="muted">Select a staff member and a completed session to read the historical questions and answers.</p><span class="privacy">Confidential staff records · Authorised administrative use</span></header>
<main class="summary"><section id="stats" class="stats" aria-label="Archive summary"></section><div class="layout"><aside class="sidebar"><label for="search">Find a staff member</label><input id="search" type="search" autocomplete="off" placeholder="Search name or staff reference"><nav id="staff-list" class="staff-list" aria-label="Staff with completed sessions"></nav></aside><section class="workspace"><div class="workspace-toolbar"><h2 id="staff-heading">Completed sessions</h2><button id="print" class="print-button" type="button">Print selected session</button></div><nav id="session-list" class="session-list" aria-label="Completed supervision sessions"></nav><article id="session-detail" class="session-detail" aria-live="polite"></article></section></div><footer class="source-notes"><p id="source"></p><p id="source-hash"></p><p>This archive contains completed records only. It has not been merged into the current staff database. Timestamps retain the source values; their timezone was not recorded.</p></footer></main>
<noscript>Enable JavaScript to browse the archive. The accompanying archive.json retains every prepared staff and supervision record.</noscript>
<script id="archive-data" type="application/json">${json}</script><script>${code}</script></body></html>`
}

export async function writeArchivePreview(input, output) {
  const archive = JSON.parse(await readFile(input, 'utf8'))
  const html = renderArchive(archive)
  await mkdir(dirname(output), { recursive: true })
  await writeFile(output, html, 'utf8')
  return { staff: archive.staff.length, sessions: archive.sessions.length, answerRows: archive.counts.answer_rows, htmlBytes: Buffer.byteLength(html) }
}

if (process.argv[1] && import.meta.url === pathToFileURL(resolve(process.argv[1])).href) {
  const input = process.argv[2]
  const output = process.argv[3]
  if (!input || !output) throw new Error('Usage: node scripts/render-supervision-archive.mjs archive.json index.html')
  console.log(JSON.stringify(await writeArchivePreview(resolve(input), resolve(output))))
}
