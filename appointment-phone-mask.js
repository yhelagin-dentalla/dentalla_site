(() => {
  const field = document.getElementById('appt-phone');
  if (!field) return;
  const mask = '+7(___)___-__-__';
  const slots = [3,4,5,7,8,9,11,12,14,15];
  const digits = value => {
    let raw = value.replace(/\D/g, '');
    if (/^\s*\+7/.test(value) || (raw.length === 11 && /^[78]/.test(raw))) raw = raw.slice(1);
    return raw.slice(0,10);
  };
  const render = raw => {
    const chars = mask.split('');
    for (let i=0;i<raw.length;i++) chars[slots[i]]=raw[i];
    return chars.join('');
  };
  const position = offset => slots.filter(p => p < offset).length;
  const cursor = i => i < slots.length ? slots[i] : mask.length;
  const write = (raw,i) => {
    field.value = render(raw);
    field.setSelectionRange(cursor(i),cursor(i));
    field.dispatchEvent(new Event('input',{bubbles:true}));
  };
  let editing = false;
  function edit(text, mode) {
    const raw = digits(field.value);
    let a = Math.min(raw.length,position(field.selectionStart ?? mask.length));
    let b = Math.min(raw.length,position(field.selectionEnd ?? mask.length));
    if (mode === 'back' && a===b && a>0) a--;
    if (mode === 'delete' && a===b && b<raw.length) b++;
    const insert = text.replace(/\D/g,'').slice(0,10-(raw.length-(b-a)));
    const next = raw.slice(0,a)+insert+raw.slice(b);
    editing=true;
    try { write(next,a+insert.length); } finally {editing=false;}
  }
  field.addEventListener('focus', () => {
    const raw=digits(field.value);
    field.value=render(raw);
    field.setSelectionRange(cursor(raw.length),cursor(raw.length));
  });
  field.addEventListener('blur', () => { if (!digits(field.value)) field.value=''; });
  field.addEventListener('beforeinput', e => {
    if (!e.cancelable || e.isComposing) return;
    if (e.inputType==='deleteContentBackward' || e.inputType==='deleteContentForward') {
      e.preventDefault(); edit('',e.inputType==='deleteContentBackward'?'back':'delete');
    } else if (e.inputType==='insertText' && e.data!==null) {
      e.preventDefault(); if (/^\d+$/.test(e.data)) edit(e.data,'insert');
    }
  });
  field.addEventListener('paste', e => {
    if (!e.clipboardData) return;
    e.preventDefault();
    const text=e.clipboardData.getData('text');
    const raw=text.replace(/\D/g,'');
    if (raw.length>=10) {
      const number=digits(text);
      editing=true; try {write(number,number.length);} finally {editing=false;}
    } else edit(raw,'insert');
  });
  field.addEventListener('input', () => {
    if (editing) return;
    const raw=digits(field.value);
    field.value=render(raw);
    field.setSelectionRange(cursor(raw.length),cursor(raw.length));
  },true);
})();