(() => {
  'use strict';
  const form = document.getElementById('appt-form');
  if (!form) return;
  const status = document.createElement('p');
  status.setAttribute('role', 'status');
  status.style.cssText = 'font-size:13px;color:#5A5248;margin:8px 0;overflow-wrap:anywhere';
  form.append(status);
  const container = document.createElement('div');
  container.id = 'appointment-smartcaptcha';
  form.append(container);
  const tokenField = document.createElement('input');
  tokenField.type = 'hidden';
  tokenField.name = 'appointment-captcha-token';
  form.append(tokenField);
  let loading, widget, pending = false, timer;
  function fail() {
    clearTimeout(timer);
    pending = false;
    tokenField.value = '';
    status.textContent = 'Не удалось пройти проверку. Попробуйте ещё раз или запишитесь по телефону клиники.';
    if (widget !== undefined && window.smartCaptcha) window.smartCaptcha.reset(widget);
  }
  function ready() {
    if (loading) return loading;
    loading = (async () => {
      const response = await fetch('smartcaptcha-config.php', {cache:'no-store'});
      if (!response.ok) throw Error('config');
      const {sitekey} = await response.json();
      if (!sitekey) throw Error('key');
      await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=explicit';
        script.async = true;
        const timeout = setTimeout(() => {script.remove(); reject(Error('timeout'));}, 12000);
        script.onload = () => {clearTimeout(timeout); resolve();};
        script.onerror = () => {clearTimeout(timeout); script.remove(); reject(Error('load'));};
        document.head.append(script);
      });
      if (!window.smartCaptcha) throw Error('widget');
      widget = window.smartCaptcha.render(container.id, {
        sitekey, invisible: true, hl: 'ru',
        callback: token => {
          if (!pending || !token) return;
          clearTimeout(timer);
          if (!form.reportValidity()) { fail(); return; }
          tokenField.value = token;
          status.textContent = 'Отправляем заявку…';
          HTMLFormElement.prototype.submit.call(form);
        }
      });
      window.smartCaptcha.subscribe(widget, 'network-error', fail);
      window.smartCaptcha.subscribe(widget, 'javascript-error', fail);
      window.smartCaptcha.subscribe(widget, 'token-expired', fail);
    })().catch(error => {loading = undefined; throw error;});
    return loading;
  }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (pending || !form.reportValidity()) return;
    pending = true;
    status.textContent = 'Выполняется проверка безопасности. При необходимости выполните задание.';
    try {
      await ready();
      if (!pending) return;
      timer = setTimeout(fail, 180000);
      window.smartCaptcha.execute(widget);
    } catch (_) { fail(); }
  });
  window.addEventListener('pageshow', event => {if (event.persisted) fail();});
})();
