<?php
/**
 * Evruni unified UI prepend.
 * Keeps the existing dashboard injector and adds the public landing sections.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

require_once __DIR__ . '/creator_nav_inject.php';

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!in_array($requestPath, ['/', '/index.php'], true)) {
    return;
}

ob_start(static function (string $html): string {
    if (strpos($html, 'class="public-page"') === false || strpos($html, 'id="evruni-public-sections"') !== false) {
        return $html;
    }

    $css = <<<'CSS'
<style id="evruni-public-sections-css">
.evruni-public-sections{width:min(1180px,calc(100% - 40px));margin:0 auto;padding:30px 0 90px;color:#fff}
.evruni-public-sections *{box-sizing:border-box}
.evruni-public-head{margin:0 0 20px}.evruni-public-kicker{font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:#9a8cff;font-weight:800;margin-bottom:9px}.evruni-public-head h2{margin:0;color:#f6f7ff;font-size:30px;line-height:1.08;letter-spacing:-.04em}.evruni-public-head p{max-width:760px;margin:9px 0 0;color:#8995ad;font-size:14px;line-height:1.6}
.evruni-feature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.evruni-card{position:relative;padding:21px;border:1px solid rgba(157,170,204,.14);border-radius:22px;background:linear-gradient(145deg,rgba(18,23,42,.94),rgba(7,11,21,.9));box-shadow:0 22px 60px rgba(0,0,0,.22);overflow:hidden;transition:.22s ease}.evruni-card:hover{transform:translateY(-4px);border-color:rgba(151,127,255,.34)}.evruni-icon{width:43px;height:43px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(145deg,rgba(139,92,246,.25),rgba(56,189,248,.13));border:1px solid rgba(171,151,255,.2);font-size:20px;margin-bottom:16px}.evruni-card h3{margin:0 0 7px;font-size:17px;color:#fff}.evruni-card p{margin:0;color:#8995ad;font-size:13px;line-height:1.55}.evruni-card a{display:inline-flex;margin-top:14px;color:#b9a8ff;text-decoration:none;font-size:12px;font-weight:800}
.evruni-section{margin-top:46px;padding:26px;border:1px solid rgba(157,170,204,.14);border-radius:26px;background:linear-gradient(135deg,rgba(18,24,44,.9),rgba(7,11,21,.86));box-shadow:0 26px 75px rgba(0,0,0,.24);overflow:hidden}.evruni-grid4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.evruni-mini{padding:18px;border:1px solid rgba(157,170,204,.11);border-radius:18px;background:rgba(5,9,18,.52)}.evruni-mini h3{margin:0 0 7px;color:#fff;font-size:15px}.evruni-mini p{margin:0;color:#8995ad;font-size:12px;line-height:1.5}
.evruni-trends{display:grid;grid-template-columns:1.25fr 1fr 1fr;gap:12px}.evruni-trend{padding:20px;border:1px solid rgba(157,170,204,.11);border-radius:18px;background:rgba(5,9,18,.52)}.evruni-trend.featured{background:linear-gradient(135deg,rgba(91,63,190,.2),rgba(31,150,210,.1))}.evruni-trend b{display:block;color:#91eaff;font-size:10px;letter-spacing:.12em;text-transform:uppercase;margin-bottom:9px}.evruni-trend h3{margin:0 0 7px;color:#fff;font-size:16px}.evruni-trend p{margin:0;color:#8995ad;font-size:13px;line-height:1.5}.evruni-cta{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:16px;padding:18px 20px;border:1px solid rgba(157,170,204,.11);border-radius:18px;background:rgba(5,9,18,.52)}.evruni-cta strong{display:block;color:#fff;font-size:14px}.evruni-cta span{display:block;color:#7f8ba5;font-size:12px;margin-top:4px}.evruni-cta a{padding:11px 16px;border-radius:12px;background:linear-gradient(135deg,#9b7cff,#5d3ce0);color:#fff;text-decoration:none;font-weight:800;font-size:12px;white-space:nowrap}
@media(max-width:900px){.evruni-feature-grid,.evruni-grid4{grid-template-columns:repeat(2,minmax(0,1fr))}.evruni-trends{grid-template-columns:1fr 1fr}.evruni-trend.featured{grid-column:1/-1}}
@media(max-width:600px){.evruni-public-sections{width:calc(100% - 24px);padding:22px 0 55px}.evruni-public-head h2{font-size:25px}.evruni-feature-grid,.evruni-grid4,.evruni-trends{grid-template-columns:1fr}.evruni-trend.featured{grid-column:auto}.evruni-section{padding:18px;margin-top:34px}.evruni-cta{display:block}.evruni-cta a{display:block;text-align:center;margin-top:14px}}
</style>
CSS;

    $sections = <<<'HTML'
<section class="evruni-public-sections" id="evruni-public-sections">
  <div class="evruni-public-head">
    <div class="evruni-public-kicker">EVRUNI AI WORKSPACE</div>
    <h2>Всё необходимое — в одном месте</h2>
    <p>Общение с AI, создание контента, исследования и работа с файлами — без переключения между десятками сервисов.</p>
  </div>
  <div class="evruni-feature-grid">
    <article class="evruni-card"><div class="evruni-icon">✦</div><h3>AI Chat</h3><p>Ответы, тексты, идеи и решение задач в одном AI-пространстве.</p><a href="/register.php">Попробовать →</a></article>
    <article class="evruni-card"><div class="evruni-icon">✧</div><h3>Image & Video</h3><p>Создавай визуальный контент из идеи и быстро превращай задумку в результат.</p><a href="/register.php">Создать →</a></article>
    <article class="evruni-card"><div class="evruni-icon">⌕</div><h3>Research</h3><p>Исследуй темы, собирай информацию и разбирай сложные вопросы с AI.</p><a href="/register.php">Исследовать →</a></article>
    <article class="evruni-card"><div class="evruni-icon">▤</div><h3>Documents</h3><p>Работай с документами и файлами прямо внутри Evruni.</p><a href="/register.php">Открыть →</a></article>
  </div>

  <section class="evruni-section">
    <div class="evruni-public-head"><div class="evruni-public-kicker">EVRUNI CREATOR</div><h2>Для стримеров и создателей</h2><p>AI-слой поверх Twitch, YouTube и Kick: полезные инструменты работают по событиям и не требуют постоянного тяжёлого анализа.</p></div>
    <div class="evruni-grid4">
      <article class="evruni-mini"><h3>🎥 Creator Tools</h3><p>AI Chat Bot, модерация, Voice Cleaner, Smart Alerts, клипы и аналитика.</p></article>
      <article class="evruni-mini"><h3>💜 Прямые донаты</h3><p>Зритель платит напрямую выбранному платёжному сервису стримера.</p></article>
      <article class="evruni-mini"><h3>🛡️ AI Film Guard</h3><p>Поиск фильма в базе, сцены, тайминги и автоматические blur/mute действия.</p></article>
      <article class="evruni-mini"><h3>⚡ Stream Copilot</h3><p>Помощь во время эфира только при нужных событиях, без постоянного AI-потока.</p></article>
    </div>
    <div class="evruni-cta"><div><strong>Creator Tools уже внутри Evruni</strong><span>Инструменты для более удобного и интерактивного стрима.</span></div><a href="/register.php">Создать аккаунт</a></div>
  </section>

  <section class="evruni-section">
    <div class="evruni-public-head"><div class="evruni-public-kicker">EVRUNI TREND LAB</div><h2>Тренды и идеи контента</h2><p>Trend Lab помогает замечать растущий интерес, разбирать причины и превращать тренд в конкретную идею.</p></div>
    <div class="evruni-trends">
      <article class="evruni-trend featured"><b>↗ TREND SIGNAL</b><h3>Что сейчас набирает внимание</h3><p>Сигналы роста интереса помогают находить перспективные темы раньше массового обсуждения.</p></article>
      <article class="evruni-trend"><b>✦ ANALYZE</b><h3>Разбор тренда</h3><p>Контекст, причины роста и связанные темы в одном понятном результате.</p></article>
      <article class="evruni-trend"><b>⚡ CREATE</b><h3>Идеи для контента</h3><p>Преврати тренд в тему для стрима, ролика, поста или AI-проекта.</p></article>
    </div>
    <div class="evruni-cta"><div><strong>Trend Lab</strong><span>Тренды, AI и Creator Tools объединены в одном пространстве.</span></div><a href="/register.php">Начать бесплатно</a></div>
  </section>
</section>
HTML;

    $html = str_replace('</main>', $css . $sections . '</main>', $html, 1);
    return $html;
});
