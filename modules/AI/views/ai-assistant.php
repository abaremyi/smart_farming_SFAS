<?php
/**
 * SFAS — AI Farming Assistant
 * File: modules/AI/views/ai-assistant.php
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
.chat-typing{display:flex;gap:4px;padding:4px 0}
.chat-typing span{width:8px;height:8px;background:var(--green-400);border-radius:50%;animation:chatTyping 1.4s infinite both}
.chat-typing span:nth-child(2){animation-delay:.2s}
.chat-typing span:nth-child(3){animation-delay:.4s}
@keyframes chatTyping{0%,80%,100%{transform:scale(0)}40%{transform:scale(1)}}
.sfas-chat-messages{height:440px;overflow-y:auto;padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;background:#fafcfa;border-radius:var(--radius-md) var(--radius-md) 0 0}
.sfas-chat-messages .chat-msg{display:flex;gap:.6rem;max-width:88%}
.sfas-chat-messages .chat-msg.user{align-self:flex-end;flex-direction:row-reverse}
.sfas-chat-messages .chat-msg.user .chat-bubble{background:var(--green-500);color:#fff;border-radius:16px 16px 4px 16px}
.sfas-chat-messages .chat-msg.assistant .chat-bubble{background:#fff;border:1px solid var(--border);border-radius:16px 16px 16px 4px}
.sfas-chat-messages .chat-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0}
.sfas-chat-messages .chat-msg.user .chat-avatar{background:var(--green-200);color:var(--green-700)}
.sfas-chat-messages .chat-msg.assistant .chat-avatar{background:var(--green-100);color:var(--green-600)}
.sfas-chat-messages .chat-bubble{padding:.7rem 1rem;font-size:.9rem;line-height:1.6;word-wrap:break-word}
.sfas-chat-messages .chat-bubble ul{margin:.4rem 0;padding-left:1.2rem}
.sfas-chat-input-row{display:flex;gap:.6rem;padding:.75rem 1.25rem;border-top:1px solid var(--border);background:#fff;border-radius:0 0 var(--radius-md) var(--radius-md)}
.sfas-chat-input-row textarea{flex:1;border:1px solid var(--border);border-radius:var(--radius-md);padding:.6rem .9rem;font-size:.875rem;resize:none;min-height:44px;max-height:120px;font-family:inherit;transition:var(--transition)}
.sfas-chat-input-row textarea:focus{outline:none;border-color:var(--green-400);box-shadow:0 0 0 3px rgba(45,154,78,.12)}
.sfas-chat-input-row button{height:44px;width:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sfas-chat-suggestions{display:flex;gap:.5rem;flex-wrap:wrap;padding:.65rem 1.25rem;border-top:1px solid var(--border);background:#f8faf8}
.sfas-chat-suggestions button{background:var(--green-50);border:1px solid var(--green-200);border-radius:99px;padding:.3rem .9rem;font-size:.75rem;font-weight:500;color:var(--green-700);cursor:pointer;transition:var(--transition);white-space:nowrap}
.sfas-chat-suggestions button:hover{background:var(--green-100);border-color:var(--green-400)}
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
      <div class="sfas-chat-suggestions">
        <button onclick="sendSuggestion(this)">What crops suit Nyagatare in Season A?</button>
        <button onclick="sendSuggestion(this)">My maize has fall armyworm — what do I do?</button>
        <button onclick="sendSuggestion(this)">Best fertilizer for Irish potato?</button>
        <button onclick="sendSuggestion(this)">When should I plant beans in Season B?</button>
        <button onclick="sendSuggestion(this)">How do I store maize long-term?</button>
      </div>

      <!-- Input Row -->
      <div class="sfas-chat-input-row">
        <textarea id="chatInput" placeholder="Ask about farming, crops, pests, soil, market prices…"
          rows="1"></textarea>
        <button class="sfas-btn sfas-btn-primary" id="sendBtn">
          <i class="ri-send-plane-fill"></i>
        </button>
      </div>

    </div>
  </div>

  <p style="text-align:center;font-size:.77rem;color:var(--text-light);margin-top:.75rem">
    <i class="ri-robot-line"></i> Powered by AI · Advice is for guidance only — always consult local extension officers for critical decisions.
  </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const B = window.BASE_URL || '';
  const chatInput = document.getElementById('chatInput');
  const sendBtn = document.getElementById('sendBtn');
  const chatMessages = document.getElementById('chatMessages');
  
  let chatHistory = [];

  // ── Auto-resize textarea ────────────────────────────────
  function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
  }

  // ── Send on Enter (Shift+Enter = newline) ───────────────
  function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  }

  // ── Send suggestion ────────────────────────────────────
  window.sendSuggestion = function(btn) {
    chatInput.value = btn.textContent;
    sendMessage();
  };

  // ── Escape for HTML display ─────────────────────────────
  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // ── Convert markdown-lite to HTML ──────────────────────
  function mdToHtml(text) {
    let html = esc(text);
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
    html = html.replace(/^### (.+)$/gm, '<strong>$1</strong>');
    html = html.replace(/^## (.+)$/gm, '<strong>$1</strong>');
    html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
    // Wrap consecutive list items
    html = html.replace(/(<li>.*<\/li>\n?)+/g, function(match) {
      return '<ul>' + match.trim() + '</ul>';
    });
    html = html.replace(/\n{2,}/g, '</p><p>');
    // Wrap in paragraphs
    if (!html.startsWith('<')) {
      html = '<p>' + html + '</p>';
    }
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  // ── Append a message bubble ─────────────────────────────
  function appendMsg(role, html) {
    const isUser = (role === 'user');
    const div = document.createElement('div');
    div.className = 'chat-msg ' + (isUser ? 'user' : 'assistant');
    
    const avatar = isUser ? 
      (window.SIPIS_USER?.initials || 'F') :
      '<i class="ri-plant-line"></i>';
    
    div.innerHTML = `
      <div class="chat-avatar">${avatar}</div>
      <div><div class="chat-bubble">${html}</div></div>
    `;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return div;
  }

  // ── Typing indicator ────────────────────────────────────
  function showTyping() {
    const div = document.createElement('div');
    div.className = 'chat-msg assistant';
    div.id = 'chatTyping';
    div.innerHTML = `
      <div class="chat-avatar"><i class="ri-plant-line"></i></div>
      <div><div class="chat-bubble"><div class="chat-typing">
        <span></span><span></span><span></span>
      </div></div></div>
    `;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }
  
  function hideTyping() {
    const el = document.getElementById('chatTyping');
    if (el) el.remove();
  }

  // ── Main send function ──────────────────────────────────
  async function sendMessage() {
    const msg = chatInput.value.trim();
    if (!msg) return;

    chatInput.value = '';
    chatInput.style.height = 'auto';
    sendBtn.disabled = true;

    // Add user bubble
    appendMsg('user', esc(msg).replace(/\n/g, '<br>'));
    chatHistory.push({ role: 'user', content: msg });
    showTyping();

    try {
      const res = await fetch(B + '/api/ai/chat', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          message: msg, 
          history: chatHistory.slice(-10) 
        })
      });
      const data = await res.json();
      hideTyping();
      
      if (data.success) {
        const html = mdToHtml(data.reply);
        appendMsg('assistant', html);
        chatHistory.push({ role: 'assistant', content: data.reply });
      } else {
        appendMsg('assistant', 
          `<span style="color:var(--red-600)"><i class="ri-error-warning-line"></i> ${esc(data.message || 'An error occurred. Please try again.')}</span>`
        );
      }
    } catch (e) {
      hideTyping();
      appendMsg('assistant', 
        '<span style="color:var(--red-600)"><i class="ri-wifi-off-line"></i> Network error. Please check your connection.</span>'
      );
    } finally {
      sendBtn.disabled = false;
      chatInput.focus();
    }
  }

  // ── Clear chat ─────────────────────────────────────────
  window.clearChat = function() {
    chatMessages.innerHTML = '';
    chatHistory = [];
    // Re-add welcome message
    const div = document.createElement('div');
    div.className = 'chat-msg assistant';
    div.innerHTML = `
      <div class="chat-avatar"><i class="ri-plant-line"></i></div>
      <div>
        <div class="chat-bubble">
          <p>Chat cleared. How can I help you with your farming today?</p>
        </div>
      </div>
    `;
    chatMessages.appendChild(div);
  };

  // ── Event listeners ────────────────────────────────────
  chatInput.addEventListener('input', function() { autoResize(this); });
  chatInput.addEventListener('keydown', handleKey);
  sendBtn.addEventListener('click', sendMessage);
  
  // Initial focus
  chatInput.focus();
});
</script>

<?php require get_layout('admin-scripts'); ?>