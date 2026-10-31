(function(){
  'use strict';
  if(!document.body || !document.body.classList.contains('public-page')) return;
  if(document.getElementById('evruni-public-product-blocks')) return;

  var style=document.createElement('style');
  style.id='evruni-public-product-blocks-css';
  style.textContent=`
    .evruni-public-products{width:min(1180px,calc(100% - 40px));margin:28px auto 0;padding-bottom:70px}
    .evruni-public-section-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin:0 0 18px}
    .evruni-public-kicker{font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:#8f82c9;font-weight:800;margin-bottom:7px}
    .evruni-public-section-head h2{margin:0;font-size:30px;line-height:1.05;letter-spacing:-.045em;color:#f4f6ff}
    .evruni-public-section-head p{margin:8px 0 0;max-width:650px;color:#8f9ab2;font-size:14px;line-height:1.55}
    .evruni-public-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .evruni-public-card{position:relative;overflow:hidden;min-height:190px;padding:20px;border:1px solid rgba(157,170,204,.13);border-radius:22px;background:linear-gradient(145deg,rgba(17,22,39,.88),rgba(7,11,21,.82));box-shadow:0 22px 60px rgba(0,0,0,.22);transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease}
    .evruni-public-card:before{content:"";position:absolute;inset:-45%;background:radial-gradient(circle,rgba(139,92,246,.13),transparent 55%);opacity:.65;pointer-events:none}
    .evruni-public-card:hover{transform:translateY(-5px);border-color:rgba(151,127,255,.32);box-shadow:0 28px 70px rgba(0,0,0,.32)}
    .evruni-public-icon{position:relative;width:42px;height:42px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(145deg,rgba(139,92,246,.24),rgba(56,189,248,.12));border:1px solid rgba(171,151,255,.2);font-size:21px;margin-bottom:18px}
    .evruni-public-card h3{position:relative;margin:0 0 7px;font-size:17px;color:#fff;letter-spacing:-.02em}
    .evruni-public-card p{position:relative;margin:0;color:#8995ad;font-size:13px;line-height:1.55}
    .evruni-public-card a{position:relative;display:inline-flex;margin-top:16px;color:#b9a8ff;text-decoration:none;font-size:12px;font-weight:800}
    .evruni-public-card a:hover{color:#8feaff}
    .evruni-public-trends{margin-top:52px;padding:26px;border:1px solid rgba(157,170,204,.13);border-radius:26px;background:linear-gradient(135deg,rgba(18,24,44,.86),rgba(7,11,21,.78));box-shadow:0 26px 75px rgba(0,0,0,.24);position:relative;overflow:hidden}
    .evruni-public-trends:after{content:"";position:absolute;width:420px;height:220px;right:-130px;top:-110px;background:radial-gradient(circle,rgba(48,196,255,.15),transparent 68%);pointer-events:none}
    .evruni-trend-grid{display:grid;grid-template-columns:1.25fr 1fr 1fr;gap:12px}
    .evruni-trend-card{position:relative;padding:18px;border:1px solid rgba(157,170,204,.10);border-radius:18px;background:rgba(6,10,20,.48)}
    .evruni-trend-card.featured{background:linear-gradient(135deg,rgba(91,63,190,.18),rgba(31,150,210,.08))}
    .evruni-trend-label{display:flex;align-items:center;gap:7px;color:#91eaff;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:10px}
    .evruni-trend-card h3{margin:0 0 7px;font-size:16px;color:#fff}
    .evruni-trend-card p{margin:0;color:#8995ad;font-size:13px;line-height:1.5}
    .evruni-public-cta{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:20px;padding:18px 20px;border-radius:19px;border:1px solid rgba(157,170,204,.12);background:rgba(6,10,20,.48)}
    .evruni-public-cta strong{display:block;color:#fff;font-size:14px;margin-bottom:4px}.evruni-public-cta span{color:#7f8ba5;font-size:12px}
    .evruni-public-cta a{display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:12px;color:#fff;text-decoration:none;font-weight:800;font-size:12px;background:linear-gradient(135deg,#9b7cff,#5d3ce0);box-shadow:0 10px 28px rgba(108,76,255,.25);white-space:nowrap}
    @media(max-width:900px){.evruni-public-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.evruni-trend-grid{grid-template-columns:1fr 1fr}.evruni-trend-card.featured{grid-column:1/-1}}
    @media(max-width:600px){.evruni-public-products{width:min(100% - 24px,1180px);margin-top:18px}.evruni-public-section-head{display:block}.evruni-public-section-head h2{font-size:25px}.evruni-public-grid{grid-template-columns:1fr}.evruni-public-card{min-height:0}.evruni-public-trends{padding:18px;margin-top:34px}.evruni-trend-grid{grid-template-columns:1fr}.evruni-trend-card.featured{grid-column:auto}.evruni-public-cta{display:block}.evruni-public-cta a{margin-top:14px;width:100%}}
  `;
  document.head.appendChild(style);

  var main=document.querySelector('.public-main');
  var hero=document.querySelector('.public-hero');
  if(!main || !hero) return;

  var wrap=document.createElement('section');
  wrap.id='evruni-public-product-blocks';
  wrap.className='evruni-public-products';
  wrap.innerHTML=`
    <div class="evruni-public-section-head">
      <div><div class="evruni-public-kicker">EVRUNI CREATOR</div><h2>Всё для стримера — внутри Evruni</h2><p>Не отдельная стриминг-платформа, а AI-слой поверх Twitch, YouTube и Kick: инструменты, которые помогают вести эфир и работать с контентом.</p></div>
    </div>
    <div class="evruni-public-grid">
      <article class="evruni-public-card"><div class="evruni-public-icon">🎥</div><h3>Creator Tools</h3><p>AI-модерация, чат-бот, Voice Cleaner, Smart Alerts, клипы, аналитика и инструменты для эфира.</p><a href="/creator-tools.php">Открыть Creator Tools →</a></article>
      <article class="evruni-public-card"><div class="evruni-public-icon">💜</div><h3>Прямые донаты</h3><p>Стример указывает свой платёжный сервис, а зритель переходит напрямую. Evruni не хранит деньги.</p><a href="/creator-tools.php">Настроить донаты →</a></article>
      <article class="evruni-public-card"><div class="evruni-public-icon">🛡️</div><h3>AI Film Guard</h3><p>Поиск фильма в базе, таймлайн сцен и автоматические действия blur/mute для защиты контента.</p><a href="/creator-tools.php">Подробнее →</a></article>
      <article class="evruni-public-card"><div class="evruni-public-icon">📊</div><h3>Stream Analytics</h3><p>События и ключевые показатели без постоянного тяжёлого AI-анализа видео и аудио.</p><a href="/creator-tools.php">Смотреть инструменты →</a></article>
    </div>

    <section class="evruni-public-trends">
      <div class="evruni-public-section-head"><div><div class="evruni-public-kicker">EVRUNI TREND LAB</div><h2>Тренды, которые превращаются в идеи</h2><p>Следи за растущими темами, оценивай интерес и используй AI, чтобы быстрее понять, о чём стоит говорить или что создавать.</p></div></div>
      <div class="evruni-trend-grid">
        <article class="evruni-trend-card featured"><div class="evruni-trend-label">↗ Live signal</div><h3>Что набирает популярность</h3><p>Trend Lab собирает сигналы и помогает находить темы с растущим интересом, а не просто выдаёт случайный список новостей.</p></article>
        <article class="evruni-trend-card"><div class="evruni-trend-label">✦ Analyze</div><h3>Разбор тренда</h3><p>Пойми, почему тема растёт, кто о ней говорит и какие идеи можно получить из сигнала.</p></article>
        <article class="evruni-trend-card"><div class="evruni-trend-label">⚡ Create</div><h3>Идеи для контента</h3><p>Преврати тренд в тему для стрима, ролика, поста или AI-проекта за несколько шагов.</p></article>
      </div>
      <div class="evruni-public-cta"><div><strong>Открой Evruni и попробуй Creator Tools + Trend Lab</strong><span>Все основные инструменты остаются в одном интерфейсе.</span></div><a href="/register.php">Создать аккаунт</a></div>
    </section>
  `;
  hero.insertAdjacentElement('afterend',wrap);
})();
