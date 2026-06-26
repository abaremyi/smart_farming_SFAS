<?php
/**
 * SFAS — AI Farming Assistant
 * File: modules/AI/views/ai-assistant.php
 *
 * Uses Claude API (claude-sonnet-4-6) via chatApi.php
 * Provides conversational farming advisory for Rwanda
 */
$pageTitle   = 'AI Farming Assistant';
$currentPage = 'ai';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require get_layout('admin-head');
?>

<style>
.chat-wrap{max-width:860px;margin:0 auto}
.chat-bubble p{margin:0;line-height:1.6}
.chat-bubble p+p{margin-top:.5rem}
.chat-bubble strong{font-weight:700}
.chat-bubble ul,.chat-bubble ol{padding-left:1.2rem;margin:.4rem 0}
.chat-bubble li{margin-bottom:.2rem}
</style>

<!-- Page Header -->
<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>AI Assistant</span>
    </div>
    <h1 class="page-title"><i class="ri-robot-2-line" style="color:var(--green-500)"></i> AI Farming Assistant</h1>
    <p class="page-sub">Ask anything about crops, pests, soil, weather, or market prices in Rwanda</p>
  </div>
  <button onclick="clearChat()" class="sfas-btn sfas-btn-outline sfas-btn-sm">
    <i class="ri-delete-bin-line"></i> Clear Chat
  </button>
</div>

<div class="chat-wrap">
  <div class="sfas-card">
    <div class="sfas-card-body" style="padding:0">

      <!-- Chat Messages -->
      <div class="sfas-chat-messages" id="chatMessages">
        <!-- Welcome message -->
        <div class="chat-msg assistant">
          <div class="chat-avatar"><i class="ri-plant-line"></i></div>
          <div>
            <div class="chat-bubble">
              <p>👋 <strong>Hello! I'm your SFAS AI Farming Assistant.</strong></p>
              <p>I can help you with:</p>
              <ul>
                <li>🌱 Crop recommendations for Nyagatare District</li>
                <li>🐛 Pest and disease identification & treatment</li>
                <li>🌧️ Seasonal planting guidance</li>
                <li>🏪 Market price trends and selling advice</li>
                <li>🌍 Soil management & fertilizer recommendations</li>
              </ul>
              <p>What would you like to know?</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick suggestions -->
      <div class="sfas-chat-suggestions" style="padding:.75rem 1.25rem">
        <button onclick="sendSuggestion(this)">What crops suit Nyagatare in Season A?</button>
        <button onclick="sendSuggestion(this)">My maize has fall armyworm — what do I do?</button>
        <button onclick="sendSuggestion(this)">Best fertilizer for Irish potato?</button>
        <button onclick="sendSuggestion(this)">When should I plant beans in Season B?</button>
        <button onclick="sendSuggestion(this)">How do I store maize long-term?</button>
      </div>

      <!-- Input Row -->
      <div class="sfas-chat-input-row">
        <textarea id="chatInput" placeholder="Ask about farming, crops, pests, soil, market prices…"
          rows="1" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
        <button class="sfas-btn sfas-btn-primary" id="sendBtn" onclick="sendMessage()">
          <i class="ri-send-plane-fill"></i>
        </button>
      </div>

    </div>
  </div>

  <p style="text-align:center;font-size:.77rem;color:var(--text-light);margin-top:.75rem">
    <i class="ri-robot-line"></i> Powered by Claude AI · Advice is for guidance only — always consult local extension officers for critical decisions.
  </p>
</div>

<script>
const B = window.BASE_URL;
const userId = window.SIPIS_USER?.id || 0;
let chatHistory = []; // { role, content }

/* ── Auto-resize textarea ──────────────────────────────── */
function autoResize(el){
  el.style.height='auto';
  el.style.height=Math.min(el.scrollHeight,120)+'px';
}

/* ── Send on Enter (Shift+Enter = newline) ─────────────── */
function handleKey(e){
  if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage();}
}

function sendSuggestion(btn){
  document.getElementById('chatInput').value=btn.textContent;
  sendMessage();
}

/* ── Append a message bubble ───────────────────────────── */
function appendMsg(role,html){
  const box=document.getElementById('chatMessages');
  const isUser=(role==='user');
  const div=document.createElement('div');
  div.className='chat-msg '+(isUser?'user':'assistant');
  div.innerHTML=`
    <div class="chat-avatar">${isUser?
      (window.SIPIS_USER?.initials||'F'):
      '<i class="ri-plant-line"></i>'}</div>
    <div><div class="chat-bubble">${html}</div></div>`;
  box.appendChild(div);
  box.scrollTop=box.scrollHeight;
  return div;
}

/* ── Typing indicator ──────────────────────────────────── */
function showTyping(){
  const box=document.getElementById('chatMessages');
  const div=document.createElement('div');
  div.className='chat-msg assistant';div.id='chatTyping';
  div.innerHTML=`<div class="chat-avatar"><i class="ri-plant-line"></i></div>
    <div><div class="chat-bubble"><div class="chat-typing">
      <span></span><span></span><span></span>
    </div></div></div>`;
  box.appendChild(div);
  box.scrollTop=box.scrollHeight;
}
function hideTyping(){
  const el=document.getElementById('chatTyping');
  if(el)el.remove();
}

/* ── Escape for HTML display ───────────────────────────── */
function esc(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}

/* ── Convert markdown-lite to HTML ────────────────────── */
function mdToHtml(text){
  return text
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
    .replace(/\*(.+?)\*/g,'<em>$1</em>')
    .replace(/^### (.+)$/gm,'<strong>$1</strong>')
    .replace(/^## (.+)$/gm,'<strong>$1</strong>')
    .replace(/^- (.+)$/gm,'<li>$1</li>')
    .replace(/(<li>.*<\/li>)/s,'<ul>$1</ul>')
    .replace(/\n{2,}/g,'</p><p>')
    .replace(/^(.)/,'<p>$1')
    .replace(/(.)$/'$1</p>');
}

/* ── Main send function ────────────────────────────────── */
async function sendMessage(){
  const input=document.getElementById('chatInput');
  const msg=input.value.trim();
  if(!msg)return;

  input.value='';input.style.height='auto';
  document.getElementById('sendBtn').disabled=true;

  // Add user bubble
  appendMsg('user',esc(msg).replace(/\n/g,'<br>'));
  chatHistory.push({role:'user',content:msg});
  showTyping();

  try{
    const res=await fetch(B+'/api/ai/chat',{
      method:'POST',
      credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({message:msg,history:chatHistory.slice(-10)})
    });
    const data=await res.json();
    hideTyping();
    if(data.success){
      const html=mdToHtml(esc(data.reply));
      appendMsg('assistant',html);
      chatHistory.push({role:'assistant',content:data.reply});
    } else {
      appendMsg('assistant',`<span style="color:var(--red-600)"><i class="ri-error-warning-line"></i> ${esc(data.message||'An error occurred. Please try again.')}</span>`);
    }
  }catch(e){
    hideTyping();
    appendMsg('assistant','<span style="color:var(--red-600)"><i class="ri-wifi-off-line"></i> Network error. Please check your connection.</span>');
  }finally{
    document.getElementById('sendBtn').disabled=false;
    document.getElementById('chatInput').focus();
  }
}

function clearChat(){
  const box=document.getElementById('chatMessages');
  box.innerHTML='';
  chatHistory=[];
  appendMsg('assistant','<p>Chat cleared. How can I help you with your farming today?</p>');
}
</script>

<?php require get_layout('admin-scripts'); ?>
