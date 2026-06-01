
function post(data) {
  var fd = new FormData();
  Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
  return fetch(window.location.pathname, {method:'POST', body:fd}).then(function(r){return r.json();});
}

// Inline edit
document.querySelectorAll('.inline-edit').forEach(function(el) {
  var timer = null;
  var fieldMap = {grad: 'grad', komentar: 'kom', firma: 'firma'};
  var savId = 'sav-' + (fieldMap[el.dataset.field] || el.dataset.field) + '-' + el.dataset.id;
  function save() {
    var sav = document.getElementById(savId);
    if (sav) { sav.style.display='inline'; }
    post({_action:'update_'+el.dataset.field, id:el.dataset.id, value:el.value})
      .then(function(){
        if (sav) setTimeout(function(){ sav.style.display='none'; }, 1200);
      })
      .catch(function(){ if (sav) sav.style.display='none'; alert('Greška pri čuvanju.'); });
  }
  el.addEventListener('input', function() { clearTimeout(timer); timer = setTimeout(save, 900); });
  el.addEventListener('blur', function() { clearTimeout(timer); save(); });
});

function toggleRead(id) {
  post({_action:'toggle_procitano', id:id}).then(function(data){
    var row   = document.getElementById('row-'+id);
    var badge = document.getElementById('badge-'+id);
    var btn   = row.querySelector('.btn-sm.mark');
    if (data.procitano) {
      row.classList.remove('neprocitano-row');
      badge.className = 'badge-ok'; badge.textContent = 'Pročitano';
      btn.textContent = '↩ Novo';
    } else {
      row.classList.add('neprocitano-row');
      badge.className = 'badge-new'; badge.textContent = 'Novo';
      btn.textContent = '✓ Pročitano';
    }
    // Odmah ažuriraj sidebar badge
    if (typeof proveritBadgeve === 'function') proveritBadgeve();
  });
}

function delRow(id) {
  if (!confirm('Brisanje ne može da se poništi. Nastaviti?')) return;
  post({_action:'delete', id:id}).then(function(data){
    if (data.ok) { var row = document.getElementById('row-'+id); if (row) row.remove(); }
  });
}

// Zatvori modal na ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeModal(); closeMailModal(); closeImModal('im-firma-modal'); closeImModal('im-kontakt-modal'); }
});

// ── IMENIK JS ──
function closeImModal(id) { var m = document.getElementById(id); if (m) m.style.display = 'none'; }
function openAddFirma() {
  document.getElementById('im-firma-id').value = '';
  document.getElementById('im-firma-naziv').value = '';
  document.getElementById('im-firma-adresa').value = '';
  document.getElementById('im-firma-drzava').value = 'Srbija';
  document.getElementById('im-firma-komentar').value = '';
  document.getElementById('im-firma-title').textContent = 'Dodaj firmu';
  document.getElementById('im-firma-err').style.display = 'none';
  document.getElementById('im-firma-modal').style.display = 'flex';
}
function openEditFirma(id, naziv, adresa, drzava, komentar) {
  document.getElementById('im-firma-id').value = id;
  document.getElementById('im-firma-naziv').value = naziv;
  document.getElementById('im-firma-adresa').value = adresa;
  document.getElementById('im-firma-drzava').value = drzava || 'Srbija';
  document.getElementById('im-firma-komentar').value = komentar;
  document.getElementById('im-firma-title').textContent = 'Izmeni firmu';
  document.getElementById('im-firma-err').style.display = 'none';
  document.getElementById('im-firma-modal').style.display = 'flex';
}
function saveFirma() {
  var id      = document.getElementById('im-firma-id').value;
  var naziv   = document.getElementById('im-firma-naziv').value.trim();
  var adresa  = document.getElementById('im-firma-adresa').value.trim();
  var drzava  = document.getElementById('im-firma-drzava').value.trim() || 'Srbija';
  var komentar= document.getElementById('im-firma-komentar').value.trim();
  var err     = document.getElementById('im-firma-err');
  err.style.display = 'none';
  if (!naziv) { err.textContent='Naziv je obavezan.'; err.style.display='block'; return; }
  var action = id ? 'imenik_edit_firma' : 'imenik_add_firma';
  post({_action:action, id:id||0, naziv:naziv, adresa:adresa, drzava:drzava, komentar:komentar})
    .then(function(d) {
      if (d.ok) { closeImModal('im-firma-modal'); location.reload(); }
      else { err.textContent = d.err||'Greška.'; err.style.display='block'; }
    });
}
function delFirma(id) {
  if (!confirm('Obrisati firmu i sve kontakte?')) return;
  post({_action:'imenik_del_firma', id:id}).then(function(d){
    if (d.ok) { location.reload(); }
  });
}
function openAddKontakt(firmaId) {
  document.getElementById('im-kontakt-id').value = '';
  document.getElementById('im-kontakt-firma-id').value = firmaId;
  document.getElementById('im-kontakt-ime').value = '';
  document.getElementById('im-kontakt-email').value = '';
  document.getElementById('im-kontakt-tel').value = '';
  document.getElementById('im-kontakt-kom').value = '';
  document.getElementById('im-kontakt-title').textContent = 'Dodaj kontakt';
  document.getElementById('im-kontakt-err').style.display = 'none';
  document.getElementById('im-kontakt-modal').style.display = 'flex';
}
function openEditKontakt(id, firmaId, ime, email, tel, kom) {
  document.getElementById('im-kontakt-id').value = id;
  document.getElementById('im-kontakt-firma-id').value = firmaId;
  document.getElementById('im-kontakt-ime').value = ime;
  document.getElementById('im-kontakt-email').value = email;
  document.getElementById('im-kontakt-tel').value = tel;
  document.getElementById('im-kontakt-kom').value = kom;
  document.getElementById('im-kontakt-title').textContent = 'Izmeni kontakt';
  document.getElementById('im-kontakt-err').style.display = 'none';
  document.getElementById('im-kontakt-modal').style.display = 'flex';
}
function saveKontakt() {
  var id      = document.getElementById('im-kontakt-id').value;
  var firmaId = document.getElementById('im-kontakt-firma-id').value;
  var ime     = document.getElementById('im-kontakt-ime').value.trim();
  var email   = document.getElementById('im-kontakt-email').value.trim();
  var tel     = document.getElementById('im-kontakt-tel').value.trim();
  var kom     = document.getElementById('im-kontakt-kom').value.trim();
  var err     = document.getElementById('im-kontakt-err');
  err.style.display = 'none';
  var action = id ? 'imenik_edit_kontakt' : 'imenik_add_kontakt';
  post({_action:action, id:id||0, firma_id:firmaId, ime:ime, email_k:email, telefon_k:tel, komentar:kom})
    .then(function(d) {
      if (d.ok) { closeImModal('im-kontakt-modal'); location.reload(); }
      else { err.textContent = d.err||'Greška.'; err.style.display='block'; }
    });
}
function delKontakt(id) {
  if (!confirm('Obrisati kontakt?')) return;
  post({_action:'imenik_del_kontakt', id:id}).then(function(d){
    if (d.ok) { location.reload(); }
  });
}

// ── TIM JS ──
function timAdd() {
  var ime      = (document.getElementById('tim-ime')       ||{}).value||'';
  var em       = (document.getElementById('tim-email')     ||{}).value||'';
  var tel      = (document.getElementById('tim-telefon')   ||{}).value||'';
  var mpass    = (document.getElementById('tim-mail-pass') ||{}).value||'';
  var user     = (document.getElementById('tim-username')  ||{}).value||'';
  var pass     = (document.getElementById('tim-pass')      ||{}).value||'';
  var uloga    = (document.getElementById('tim-uloga')     ||{}).value||'';
  var telegram = (document.getElementById('tim-telegram')  ||{}).value||'';
  var platforma = (document.getElementById('tim-platforma')  ||{}).value||'android';
  var platforma2= (document.getElementById('tim-platforma2') ||{}).value||'';
  var err  = document.getElementById('tim-add-err');
  var ok   = document.getElementById('tim-add-ok');
  if (err) err.style.display='none';
  if (ok)  ok.style.display='none';
  post({_action:'tim_add', ime:ime, email_k:em, telefon_u:tel, mail_pass_u:mpass,
        username_k:user, password_k:pass, uloga:uloga, telegram_username:telegram,
        platforma:platforma, platforma2:platforma2})
    .then(function(d) {
      if (d.ok) {
        if (ok) { ok.style.display='block'; }
        setTimeout(function(){
          document.getElementById('tim-add-modal').style.display='none';
          location.reload();
        }, 800);
      } else {
        if (err) { err.textContent = d.err || 'Greška.'; err.style.display='block'; }
      }
    });
}

function timToggle(id) {
  post({_action:'tim_toggle_aktivan', id:id}).then(function(d){
    if (!d.ok) return;
    var badge = document.getElementById('tim-badge-'+id);
    var btn   = document.getElementById('tim-toggle-'+id);
    if (d.aktivan) {
      if (badge) { badge.className='badge-aktivan'; badge.textContent='Aktivan'; }
      if (btn)   { btn.className='btn-sm del'; btn.textContent='Deaktiviraj'; }
    } else {
      if (badge) { badge.className='badge-neaktivan'; badge.textContent='Neaktivan'; }
      if (btn)   { btn.className='btn-sm mark'; btn.textContent='Aktiviraj'; }
    }
  });
}

function timDelete(id) {
  if (!confirm('Obrisati korisnika? Ovo se ne može poništiti.')) return;
  post({_action:'tim_delete', id:id}).then(function(d){
    if (d.ok) { var row = document.getElementById('tim-row-'+id); if (row) row.remove(); }
    else { alert(d.err || 'Greška.'); }
  });
}

function timResetPass(id, ime) {
  var nova = prompt('Nova lozinka za ' + ime + ' (min. 6 karaktera):');
  if (!nova || nova.length < 6) { alert('Lozinka mora imati najmanje 6 karaktera.'); return; }
  post({_action:'tim_reset_pass', id:id, password_k:nova}).then(function(d){
    if (d.ok) { alert('Lozinka je promenjena.'); }
    else { alert(d.err || 'Greška.'); }
  });
}

document.querySelectorAll('.tim-uloga-select').forEach(function(sel) {
  sel.addEventListener('change', function() {
    post({_action:'tim_change_uloga', id:this.dataset.id, uloga:this.value})
      .then(function(d){
        if (d.ok) location.reload();
        else alert('Greška pri promeni uloge.');
      });
  });
});

function timToggleImenik(id) {
  post({_action:'tim_toggle_imenik', id:id}).then(function(d){
    if (!d.ok) { alert('Greška.'); return; }
    var btn = document.getElementById('tim-imenik-'+id);
    if (btn) {
      if (d.vidi_imenik) { btn.className='btn-sm mark'; btn.textContent='📖 Imenik: DA'; }
      else { btn.className='btn-sm'; btn.textContent='📖 Imenik: NE'; btn.style.color='var(--muted)'; }
    }
  });
}

function openTimAddModal() {
  ['tim-ime','tim-email','tim-telefon','tim-mail-pass','tim-telegram','tim-username','tim-pass'].forEach(function(id){
    var el = document.getElementById(id); if(el) el.value='';
  });
  var sel = document.getElementById('tim-uloga'); if(sel) sel.selectedIndex=0;
  var plt = document.getElementById('tim-platforma'); if(plt) plt.value='android';
  var plt2 = document.getElementById('tim-platforma2'); if(plt2) plt2.value='';
  var err = document.getElementById('tim-add-err'); if(err) err.style.display='none';
  var ok  = document.getElementById('tim-add-ok');  if(ok)  ok.style.display='none';
  document.getElementById('tim-add-modal').style.display = 'flex';
}

function openTimEdit(id, ime, email, telefon, telegram, platforma, platforma2) {
  document.getElementById('tim-edit-id').value       = id;
  document.getElementById('tim-edit-ime').value      = ime;
  document.getElementById('tim-edit-email').value    = email;
  document.getElementById('tim-edit-telefon').value  = telefon;
  document.getElementById('tim-edit-mpass').value    = '';
  document.getElementById('tim-edit-telegram').value = telegram || '';
  var plt = document.getElementById('tim-edit-platforma');
  if (plt) plt.value = platforma || 'android';
  var plt2 = document.getElementById('tim-edit-platforma2');
  if (plt2) plt2.value = platforma2 || '';
  document.getElementById('tim-edit-err').style.display = 'none';
  document.getElementById('tim-edit-modal').style.display = 'flex';
}

function saveTimEdit() {
  var id       = document.getElementById('tim-edit-id').value;
  var ime      = document.getElementById('tim-edit-ime').value.trim();
  var email    = document.getElementById('tim-edit-email').value.trim();
  var tel      = document.getElementById('tim-edit-telefon').value.trim();
  var mpass    = document.getElementById('tim-edit-mpass').value;
  var telegram = document.getElementById('tim-edit-telegram').value.trim();
  var platforma = (document.getElementById('tim-edit-platforma') ||{}).value||'android';
  var platforma2= (document.getElementById('tim-edit-platforma2')||{}).value||'';
  var err      = document.getElementById('tim-edit-err');
  err.style.display = 'none';
  if (!ime) { err.textContent='Ime je obavezno.'; err.style.display='block'; return; }
  post({_action:'tim_edit_user', id:id, ime:ime, email_k:email, telefon_u:tel,
        mail_pass_u:mpass, telegram_username:telegram, platforma:platforma, platforma2:platforma2})
    .then(function(d) {
      if (d.ok) { document.getElementById('tim-edit-modal').style.display='none'; location.reload(); }
      else { err.textContent = d.err||'Greška.'; err.style.display='block'; }
    });
}

// ── MAIL MODAL ──
var _mailContext   = 'forme';
var _selectedFiles = [];

// ── File Manager ──
function renderFileList() {
  var fl = document.getElementById('mail-file-list');
  if (!fl) return;
  fl.innerHTML = '';
  _selectedFiles.forEach(function(f, idx) {
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;align-items:center;gap:8px;padding:5px 10px;background:#eef3fb;border-radius:6px;margin-bottom:3px;';
    var info = document.createElement('span');
    info.style.cssText = 'font-size:12px;color:#1a3a6e;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
    info.textContent = '📎 ' + f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)';
    var xBtn = document.createElement('button');
    xBtn.innerHTML = '&times;';
    xBtn.title = 'Ukloni';
    xBtn.style.cssText = 'background:none;border:none;color:#dc2626;cursor:pointer;font-size:16px;padding:0;line-height:1;flex-shrink:0;';
    xBtn.onclick = (function(i) {
      return function(e) { e.preventDefault(); _selectedFiles.splice(i, 1); renderFileList(); };
    })(idx);
    row.appendChild(info);
    row.appendChild(xBtn);
    fl.appendChild(row);
  });
}

function bindFileInput() {
  var fi = document.getElementById('mail-fajlovi');
  if (!fi) return;
  fi.onchange = function() {
    var err = document.getElementById('mail-file-err');
    if (err) err.style.display = 'none';
    Array.from(this.files || []).forEach(function(f) {
      if (f.size > 50 * 1024 * 1024) {
        if (err) { err.textContent = 'Fajl "' + f.name + '" je prevelik. Max 50 MB.'; err.style.display = 'block'; }
        return;
      }
      var dup = _selectedFiles.some(function(sf) { return sf.name === f.name && sf.size === f.size; });
      if (!dup) _selectedFiles.push(f);
    });
    this.value = '';
    renderFileList();
  };
}

// ── Mail Modal ──
function openMailModal(email, ime, usluga, context) {
  _mailContext = context || 'forme';
  _selectedFiles = [];

  var modal = document.getElementById('mail-modal');
  if (!modal) return;
  var err = document.getElementById('mail-err');
  var ok  = document.getElementById('mail-ok');
  var btn = document.getElementById('mail-send-btn');
  var attachWrap = document.getElementById('mail-attach-wrap');

  document.getElementById('mail-to').value      = email;
  document.getElementById('mail-subject').value = usluga ? 'Odgovor na Vašu poruku za ' + usluga : 'Ekošarna';
  document.getElementById('mail-body').value    = '';
  if (err) err.style.display = 'none';
  if (ok)  ok.style.display  = 'none';
  if (btn) { btn.disabled = false; btn.textContent = 'Pošalji'; }

  var fl = document.getElementById('mail-file-list');
  var fe = document.getElementById('mail-file-err');
  var fi = document.getElementById('mail-fajlovi');
  if (fl) fl.innerHTML = '';
  if (fe) fe.style.display = 'none';
  if (fi) fi.value = '';

  if (attachWrap) attachWrap.style.display = (_mailContext === 'imenik') ? 'block' : 'none';

  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  bindFileInput();

  setTimeout(function() {
    var b = document.getElementById('mail-body');
    if (b) b.focus();
  }, 100);
}

function closeMailModal() {
  var modal = document.getElementById('mail-modal');
  if (modal) modal.style.display = 'none';
  document.body.style.overflow = '';
  _selectedFiles = [];
}

// ── Loader overlay sa progress barom ──
function showLoader(msg, pct) {
  var ov = document.getElementById('mail-loader');
  if (!ov) {
    ov = document.createElement('div');
    ov.id = 'mail-loader';
    ov.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.55);z-index:19999;display:flex;align-items:center;justify-content:center;';
    ov.innerHTML = '<div style="background:#fff;border-radius:14px;padding:32px 40px;text-align:center;box-shadow:0 8px 40px #0005;min-width:260px;">'
      + '<div style="width:44px;height:44px;border:4px solid #dce8f6;border-top-color:#1a3a6e;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px;"></div>'
      + '<div id="mail-loader-msg" style="color:#1a3a6e;font-weight:600;font-size:14px;margin-bottom:14px;">Slanje...</div>'
      + '<div style="background:#eef3fb;border-radius:99px;height:8px;overflow:hidden;">'
      +   '<div id="mail-loader-bar" style="background:#1a3a6e;height:8px;width:0%;border-radius:99px;transition:width .3s ease;"></div>'
      + '</div>'
      + '<div id="mail-loader-pct" style="color:#5d7a96;font-size:12px;margin-top:8px;">0%</div>'
      + '</div>';
    var style = document.createElement('style');
    style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
    document.body.appendChild(ov);
  } else {
    ov.style.display = 'flex';
  }
  var m   = document.getElementById('mail-loader-msg');
  var bar = document.getElementById('mail-loader-bar');
  var pctEl = document.getElementById('mail-loader-pct');
  if (m) m.textContent = msg || 'Slanje...';
  if (bar) bar.style.width = (pct || 0) + '%';
  if (pctEl) pctEl.textContent = (pct || 0) + '%';
}

function updateLoader(pct, msg) {
  var bar   = document.getElementById('mail-loader-bar');
  var pctEl = document.getElementById('mail-loader-pct');
  var m     = document.getElementById('mail-loader-msg');
  if (bar)   bar.style.width = pct + '%';
  if (pctEl) pctEl.textContent = pct + '%';
  if (msg && m) m.textContent = msg;
}

function hideLoader() {
  var ov = document.getElementById('mail-loader');
  if (ov) ov.style.display = 'none';
}

function sendMail() {
  var to      = document.getElementById('mail-to').value;
  var subject = (document.getElementById('mail-subject').value || '').trim();
  var body    = (document.getElementById('mail-body').value || '').trim();
  var err     = document.getElementById('mail-err');
  var ok      = document.getElementById('mail-ok');
  var btn     = document.getElementById('mail-send-btn');
  var mfe     = document.getElementById('mail-file-err');

  if (err) err.style.display = 'none';
  if (ok)  ok.style.display  = 'none';
  if (mfe) mfe.style.display = 'none';

  if (!subject || !body) {
    if (err) { err.textContent = 'Naslov i poruka su obavezni.'; err.style.display = 'block'; }
    return;
  }

  if (btn) { btn.disabled = true; btn.textContent = 'Slanje...'; }

  var hasFiles = _selectedFiles.length > 0;
  var totalSize = _selectedFiles.reduce(function(s, f){ return s + f.size; }, 0);
  var sizeStr = hasFiles ? ' (' + (totalSize/1024/1024).toFixed(1) + ' MB)' : '';

  showLoader('Priprema za slanje...', 0);

  var fd = new FormData();
  fd.append('_action',      'send_mail');
  fd.append('to_email',     to);
  fd.append('subject',      subject);
  fd.append('msg_body',     body);
  fd.append('from_context', _mailContext);

  _selectedFiles.forEach(function(f) {
    fd.append('mail_fajlovi[]', f, f.name);
  });

  var xhr = new XMLHttpRequest();
  xhr.open('POST', window.location.pathname, true);

  xhr.upload.onprogress = function(e) {
    if (e.lengthComputable) {
      var pct = Math.round((e.loaded / e.total) * 95);
      var loaded = (e.loaded / 1024 / 1024).toFixed(1);
      var total  = (e.total  / 1024 / 1024).toFixed(1);
      updateLoader(pct, 'Slanje' + sizeStr + ': ' + loaded + ' / ' + total + ' MB');
    }
  };

  xhr.upload.onload = function() {
    updateLoader(97, 'Server obrađuje...');
  };

  xhr.onload = function() {
    hideLoader();
    try {
      var d = JSON.parse(xhr.responseText);
      if (d.ok) {
        if (ok) ok.style.display = 'block';
        if (btn) btn.textContent = '✓ Poslato';
        _selectedFiles = [];
        renderFileList();
        setTimeout(closeMailModal, 1500);
      } else {
        if (err) { err.textContent = d.err || 'Greška pri slanju.'; err.style.display = 'block'; }
        if (btn) { btn.disabled = false; btn.textContent = 'Pošalji'; }
      }
    } catch(e) {
      if (err) { err.textContent = 'Greška: neispravan odgovor servera.'; err.style.display = 'block'; }
      if (btn) { btn.disabled = false; btn.textContent = 'Pošalji'; }
    }
  };

  xhr.onerror = function() {
    hideLoader();
    if (err) { err.textContent = 'Mrežna greška pri slanju.'; err.style.display = 'block'; }
    if (btn) { btn.disabled = false; btn.textContent = 'Pošalji'; }
  };

  xhr.send(fd);
}

// ── FAJL MODAL ──
function openModal(url, type) {
  var modal = document.getElementById('fajl-modal');
  var img   = document.getElementById('modal-img');
  var pdf   = document.getElementById('modal-pdf');
  var dl    = document.getElementById('modal-download');
  img.style.display = 'none'; pdf.style.display = 'none';
  if (type === 'img') { img.src = url; img.style.display = 'block'; }
  else { pdf.src = url; pdf.style.display = 'block'; }
  dl.href = url;
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  var modal = document.getElementById('fajl-modal');
  modal.style.display = 'none';
  document.getElementById('modal-img').src = '';
  document.getElementById('modal-pdf').src = '';
  document.body.style.overflow = '';
}
