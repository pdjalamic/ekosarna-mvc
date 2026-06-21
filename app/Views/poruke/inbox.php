<?php /* Poruke — chat (WhatsApp/Viber stil). Grupa „Ekošarna" = sag 0. */ ?>
<style>
.chat-wrap{display:flex;gap:0;height:calc(100vh - 130px);min-height:420px;border:1px solid var(--light2);border-radius:14px;overflow:hidden;background:#fff;}
.chat-lista{width:330px;flex-shrink:0;border-right:1px solid var(--light2);display:flex;flex-direction:column;background:#fff;}
.chat-lista-head{padding:14px 16px;border-bottom:1px solid var(--light2);}
.chat-lista-head h1{font-size:18px;font-weight:800;color:var(--blue);margin:0;}
.chat-konv{flex:1;overflow-y:auto;}
.konv-row{display:flex;align-items:center;gap:11px;padding:11px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .12s;}
.konv-row:hover{background:#f8fafc;}
.konv-row.active{background:#eef2ff;}
.konv-av{width:42px;height:42px;border-radius:50%;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;}
.konv-av.grupa{background:#0891b2;}
.konv-mid{flex:1;min-width:0;}
.konv-ime{font-weight:700;font-size:14px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.konv-prev{font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;}
.konv-right{display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;}
.konv-time{font-size:11px;color:var(--muted);}
.konv-badge{background:#e53935;color:#fff;border-radius:99px;font-size:11px;font-weight:800;min-width:18px;height:18px;padding:0 5px;display:flex;align-items:center;justify-content:center;}
.chat-thread{flex:1;display:flex;flex-direction:column;min-width:0;background:#f8fafc;}
.chat-thread-head{padding:12px 16px;border-bottom:1px solid var(--light2);background:#fff;display:flex;align-items:center;gap:10px;font-weight:700;color:var(--blue);}
.chat-back{display:none;background:var(--light);border:none;width:32px;height:32px;border-radius:50%;font-size:18px;cursor:pointer;color:var(--text);}
.chat-poruke{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px;}
.chat-empty{margin:auto;color:var(--muted);font-size:14px;text-align:center;}
.bub{max-width:74%;padding:8px 12px;border-radius:12px;font-size:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word;}
.bub.moja{background:var(--blue);color:#fff;align-self:flex-end;border-bottom-right-radius:3px;}
.bub.tudja{background:#fff;color:var(--text);align-self:flex-start;border:1px solid var(--light2);border-bottom-left-radius:3px;}
.bub-autor{font-size:11px;font-weight:700;opacity:.8;margin-bottom:2px;color:#0891b2;}
.bub-time{font-size:10px;opacity:.6;margin-top:3px;text-align:right;}
.chat-compose{display:flex;gap:8px;padding:12px;border-top:1px solid var(--light2);background:#fff;}
.chat-compose input{flex:1;border:1.5px solid var(--light2);border-radius:22px;padding:10px 16px;font-size:14px;outline:none;background:var(--light);}
.chat-compose button{background:var(--blue);color:#fff;border:none;width:42px;height:42px;border-radius:50%;font-size:20px;cursor:pointer;flex-shrink:0;}
.chat-none{margin:auto;color:var(--muted);font-size:14px;}
@media (max-width:900px){
  .chat-wrap{height:calc(100vh - 150px);border:none;border-radius:0;}
  .chat-lista{width:100%;}
  .chat-thread{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:9000;}
  .chat-wrap.otvoren .chat-lista{display:none;}
  .chat-wrap.otvoren .chat-thread{display:flex;}
  .chat-back{display:flex;align-items:center;justify-content:center;}
}
</style>

<div class="chat-wrap" id="chat-wrap">
    <div class="chat-lista">
        <div class="chat-lista-head"><h1>Poruke</h1></div>
        <div class="chat-konv" id="chat-konv"></div>
    </div>
    <div class="chat-thread">
        <div class="chat-thread-head">
            <button class="chat-back" onclick="zatvoriRazgovor()" aria-label="Nazad">←</button>
            <span id="chat-naslov">Izaberi razgovor</span>
        </div>
        <div class="chat-poruke" id="chat-poruke"><div class="chat-none">Izaberi člana tima ili grupu „Ekošarna" levo.</div></div>
        <div class="chat-compose">
            <input type="text" id="chat-input" placeholder="Napiši poruku…" autocomplete="off"
                   onkeydown="if(event.key==='Enter')posaljiPoruku()">
            <button onclick="posaljiPoruku()" aria-label="Pošalji">→</button>
        </div>
    </div>
</div>

<script>
var _uid       = <?= (int)$uid ?>;
var _clanovi   = <?= json_encode($clanovi) ?>;
var _razgovori = <?= json_encode((object)$razgovori) ?>;
var _otvori    = <?= $otvori === null ? 'null' : (int)$otvori ?>;
var _sag       = null;          // trenutno otvoren razgovor (0 = grupa)

function esc(s){ var d=document.createElement('div'); d.textContent=s==null?'':String(s); return d.innerHTML; }
function inicijali(ime){ return (ime||'?').trim().split(/\s+/).map(function(w){return w[0]||'';}).slice(0,2).join('').toUpperCase(); }
function hhmm(ts){ return ts ? String(ts).substring(11,16) : ''; }

function imeSag(sag){
    if (sag === 0) return '🏢 Ekošarna (svi)';
    var c = _clanovi.find(function(x){ return x.id == sag; });
    return c ? c.ime : 'Razgovor';
}

function renderKonv(){
    var wrap = document.getElementById('chat-konv');
    wrap.innerHTML = '';
    wrap.appendChild(konvRow(0, '🏢 Ekošarna (svi)', _razgovori[0], true));

    var clanovi = _clanovi.slice();
    clanovi.sort(function(a,b){
        var ra=_razgovori[a.id], rb=_razgovori[b.id];
        var ta=(ra&&ra.created_at)?ra.created_at:'', tb=(rb&&rb.created_at)?rb.created_at:'';
        if (ta && tb) return tb < ta ? -1 : (tb > ta ? 1 : 0);
        if (ta) return -1; if (tb) return 1;
        return (a.ime||'').localeCompare(b.ime||'');
    });
    clanovi.forEach(function(c){ wrap.appendChild(konvRow(c.id, c.ime, _razgovori[c.id], false)); });
}

function konvRow(sag, ime, r, grupa){
    var row = document.createElement('div');
    row.className = 'konv-row' + (_sag === sag ? ' active' : '');
    row.onclick = function(){ otvoriRazgovor(sag); };
    var unread = r && r.unread ? r.unread : 0;
    var prev = r && r.sadrzaj ? r.sadrzaj : (grupa ? 'Grupna prepiska tima' : 'Nema poruka');
    if (r && r.autor_ime && r.posiljalac_id == _uid) prev = 'Ti: ' + prev;
    row.innerHTML =
        '<div class="konv-av' + (grupa?' grupa':'') + '">' + (grupa?'🏢':esc(inicijali(ime))) + '</div>' +
        '<div class="konv-mid"><div class="konv-ime">' + esc(ime) + '</div>' +
        '<div class="konv-prev">' + esc(prev) + '</div></div>' +
        '<div class="konv-right"><span class="konv-time">' + (r&&r.created_at?hhmm(r.created_at):'') + '</span>' +
        (unread>0 ? '<span class="konv-badge">' + unread + '</span>' : '') + '</div>';
    return row;
}

function otvoriRazgovor(sag){
    _sag = sag;
    document.getElementById('chat-naslov').textContent = imeSag(sag);
    document.getElementById('chat-wrap').classList.add('otvoren');
    if (_razgovori[sag]) _razgovori[sag].unread = 0;
    renderKonv();
    ucitajPoruke(true);
    setTimeout(function(){ var i=document.getElementById('chat-input'); if(i) i.focus(); }, 50);
}

function zatvoriRazgovor(){
    _sag = null;
    document.getElementById('chat-wrap').classList.remove('otvoren');
    renderKonv();
}

function ucitajPoruke(scroll){
    if (_sag === null) return;
    var fd = new FormData(); fd.append('_poruke_action','poruke_thread'); fd.append('sa', _sag);
    fetch('', {method:'POST', body:fd}).then(function(r){return r.json();}).then(function(d){
        if (!d.ok) return;
        var wrap = document.getElementById('chat-poruke');
        if (!d.poruke.length){ wrap.innerHTML = '<div class="chat-empty">Još nema poruka. Napiši prvu 👇</div>'; return; }
        var html = '';
        d.poruke.forEach(function(p){
            html += '<div class="bub ' + (p.moja?'moja':'tudja') + '">' +
                    (p.moja ? '' : '<div class="bub-autor">' + esc(p.autor_ime) + '</div>') +
                    esc(p.sadrzaj) +
                    '<div class="bub-time">' + hhmm(p.created_at) + '</div></div>';
        });
        wrap.innerHTML = html;
        if (scroll) wrap.scrollTop = wrap.scrollHeight;
        else if (wrap.scrollHeight - wrap.scrollTop - wrap.clientHeight < 120) wrap.scrollTop = wrap.scrollHeight;
    });
}

function posaljiPoruku(){
    if (_sag === null) return;
    var inp = document.getElementById('chat-input');
    var txt = inp.value.trim();
    if (!txt) return;
    inp.value = '';
    var fd = new FormData(); fd.append('_poruke_action','poruke_send'); fd.append('sa', _sag); fd.append('sadrzaj', txt);
    fetch('', {method:'POST', body:fd}).then(function(r){return r.json();}).then(function(d){
        if (d.ok){ ucitajPoruke(true); osveziKonv(); }
        else { alert(d.err || 'Greška pri slanju.'); inp.value = txt; }
    });
}

function osveziKonv(){
    var fd = new FormData(); fd.append('_poruke_action','poruke_konverzacije');
    fetch('', {method:'POST', body:fd}).then(function(r){return r.json();}).then(function(d){
        if (d.ok){ _razgovori = d.razgovori || {}; if (_sag !== null && _razgovori[_sag]) _razgovori[_sag].unread = 0; renderKonv(); }
    });
}

renderKonv();
if (_otvori !== null) otvoriRazgovor(_otvori);

// Jedan globalni poll: osveži listu (badge) + otvorenu prepisku
setInterval(function(){
    osveziKonv();
    if (_sag !== null) ucitajPoruke(false);
}, 10000);
</script>
