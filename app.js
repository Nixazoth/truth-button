const API = 'api/api.php';
let currentGroup = null;

// Utils
async function api(payload){
  const res = await fetch(API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  return res.json();
}

// DOM refs
const inputGroupName = document.getElementById('inputGroupName');
const btnCreateGroup = document.getElementById('btnCreateGroup');
const inputGroupCode = document.getElementById('inputGroupCode');
const btnJoinGroup = document.getElementById('btnJoinGroup');
const groupInfo = document.getElementById('groupInfo');
const questionBox = document.getElementById('questionBox');
const answerControls = document.getElementById('answerControls');
const emojiBtns = document.getElementsByClassName('emojiBtn');
const inputWord = document.getElementById('inputWord');
const btnSendAnswer = document.getElementById('btnSendAnswer');
const btnNewQuestion = document.getElementById('btnNewQuestion');
const pulseBox = document.getElementById('pulseBox');
const placesList = document.getElementById('placesList');
const btnShareCard = document.getElementById('btnShareCard');

// Handlers
btnCreateGroup.onclick = async ()=>{
  const name = inputGroupName.value.trim();
  if(!name) return alert('Donne un nom');
  const r = await api({action:'create_group',name});
  if(r.success){ currentGroup = r.group; updateUI(); alert('Groupe créé: '+currentGroup.code);} else alert(r.error||'err');
}
btnJoinGroup.onclick = async ()=>{
  const code = inputGroupCode.value.trim();
  if(!code) return alert('Donne un code');
  const r = await api({action:'join_group',code});
  if(r.success){ currentGroup = r.group; updateUI(); } else alert(r.error||'not found');
}

btnNewQuestion.onclick = async ()=>{
  if(!currentGroup) return alert('Joins un groupe d\'abord');
  const q = prompt('Tape la question du jour (ex: Mood today ?)');
  if(!q) return;
  const r = await api({action:'create_question',groupId:currentGroup.id,question:q});
  if(r.success){ loadQuestion(); } else alert(r.error||'err');
}

for(let b of emojiBtns){ b.onclick = ()=>{ inputWord.value = ''; sendAnswer(b.textContent); } }
btnSendAnswer.onclick = ()=>{ sendAnswer(inputWord.value.trim()||null); }

btnShareCard.onclick = ()=>{
  if(!currentGroup) return alert('Joins un groupe');
  // simple share card: open a new window with snapshot text
  const text = `VibeButton • ${currentGroup.name} — ${pulseBox.textContent}`;
  const w = window.open('','_blank','width=420,height=780');
  w.document.write(`<pre style="font-family:Inter;padding:20px;background:#0b0b0c;color:#fff">${text}</pre>`);
}

async function sendAnswer(value){
  if(!currentGroup) return alert('Joins un groupe');
  const r = await api({action:'answer_question',groupId:currentGroup.id,emoji:(value && /\p{Extended_Pictographic}/u.test(value))?value:null,word:(!/\p{Extended_Pictographic}/u.test(value||'') && value)?value:null});
  if(r.success){ loadPulse(); loadQuestion(); inputWord.value=''; } else alert(r.error||'err');
}

function updateUI(){
  if(!currentGroup){ groupInfo.textContent='Aucun groupe sélectionné.'; answerControls.classList.add('hidden'); return; }
  groupInfo.textContent = `Groupe: ${currentGroup.name} • Code: ${currentGroup.code}`;
  answerControls.classList.remove('hidden');
  loadQuestion(); loadPulse();
}

async function loadQuestion(){
  if(!currentGroup) return;
  const r = await api({action:'get_question',groupId:currentGroup.id});
  if(r.success && r.question){ questionBox.textContent = r.question.question; } else questionBox.textContent = 'Aucune question pour l\'instant.';
}

async function loadPulse(){
  if(!currentGroup) return;
  const r = await api({action:'get_pulse',groupId:currentGroup.id});
  if(r.success){ pulseBox.textContent = `Vibe dominante: ${r.pulse.mood||'—'} (${r.pulse.count||0} réponses)`; placesList.innerHTML=''; (r.places||[]).forEach(p=>{ const li=document.createElement('li'); li.textContent=`${p.name} — ${p.tags.join(', ')}`; placesList.appendChild(li); }); }
}

// init: try load last used group in localStorage
(function(){
  const saved = localStorage.getItem('vibegroup');
  if(saved) currentGroup = JSON.parse(saved);
  if(currentGroup) updateUI();
})();

// save group when changes
setInterval(()=>{ if(currentGroup) localStorage.setItem('vibegroup',JSON.stringify(currentGroup)); },2000);
