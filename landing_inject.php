<?php
/**
 * Public landing enhancement layer.
 * Injects the product sections directly into the existing public landing.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!in_array($requestPath, ['/', '/index.php'], true)) {
    return;
}

ob_start(static function (string $html): string {
    if (strpos($html, 'public-page') === false || strpos($html, 'evruni-public-sections') !== false) {
        return $html;
    }

    $css = <<<'CSS'
<style id="evruni-public-sections-css">
.evruni-public-sections{width:min(1180px,calc(100% - 40px));margin:0 auto;padding:20px 0 90px;color:#fff}
.evruni-public-sections *{box-sizing:border-box}
.evruni-public-kicker{font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:#8f82c9;font-weight:800;margin-bottom:8px}
.evruni-public-head{margin:0 0 20px}.evruni-public-head h2{margin:0;color:#f5f7ff;font-size:30px;line-height:1.05;letter-spacing:-.045em}.evruni-public-head p{max-width:700px;margin:9px 0 0;color:#8995ad;font-size:14px;line-height:1.6}
.evruni-feature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.evruni-feature-card{position:relative;min-height:185px;padding:20px;border:1px solid rgba(157,170,204,.13);border-radius:22px;background:linear-gradient(145deg,rgba(17,22,39,.92),rgba(7,11,21,.86));box-shadow:0 22px 60px rgba(0,0,0,.22);overflow:hidden;transition:.22s ease}
.evruni-feature-card:before{content:"";position:absolute;width:220px;height:180px;right:-110px;top:-100px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.18),transparent 68%);pointer-events:none}.evruni-feature-card:hover{transform:translateY(-4px);border-color:rgba(151,127,255,.32);box-shadow:0 28px 70px rgba(0,0,0,.32)}
.evruni-feature-icon{position:relative;width:42px;height:42px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(145deg,rgba(139,92,246,.24),rgba(56,189,248,.12));border:1px solid rgba(171,151,255,.2);font-size:20px;margin-bottom:17px}.evruni-feature-card h3{position:relative;margin:0 0 7px;font-size:17px;color:#fff}.evruni-feature-card p{position:relative;margin:0;color:#8995ad;font-size:13px;line-height:1.55}
.evruni-feature-link{position:relative;display:inline-flex;margin-top:14px;color:#b9a8ff;text-decoration:none;font-size:12px;font-weight:800}.evruni-feature-link:hover{color:#8feaff}
.evruni-creator-section,.evruni-trend-section{position:relative;margin-top:52px;padding:26px;border:1px solid rgba(157,170,204,.13);border-radius:26px;background:linear-gradient(135deg,rgba(18,24,44,.88),rgba(7,11,21,.8));box-shadow:0 26px 75px rgba(0,0,0,.24);overflow:hidden}.evruni-creator-section:after,.evruni-trend-section:after{content:"";position:absolute;width:430px;height:240px;right:-150px;top:-120px;background:radial-gradient(circle,rgba(48,196,255,.14),transparent 68%);pointer-events:none}
.evruni-mini-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;position:relative}.evruni-mini-card{padding:17px;border:1px solid rgba(157,170,204,.1);border-radius:18px;background:rgba(6,10,20,.48)}.evruni-mini-card h3{margin:0 0 7px;color:#fff;font-size:15px}.evruni-mini-card p{margin:0;color:#8995ad;font-size:12px;line-height:1.5}
.evruni-trend-grid{display:grid;grid-template-columns:1.25fr 1fr 1fr;gap:12px;position:relative}.evruni-trend-card{padding:18px;border:1px solid rgba(157,170,204,.1);border-radius:18px;background:rgba(6,10,20,.48)}.evruni-trend-card.featured{background:linear-gradient(135deg,rgba(91,63,190,.18),rgba(31,150,210,.08))}.evruni-trend-label{display:flex;align-items:center;gap:7px;color:#91eaff;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:10px}.evruni-trend-card h3{margin:0 0 7px;font-size:16px;color:#fff}.evruni-trend-card p{margin:0;color:#8995ad;font-size:13px;line-height:1.5}
.evruni-public-cta{position:relative;display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:18px;padding:18px 20px;border-radius:19px;border:1px solid rgba(157,170,204,.12);background:rgba(6,10,20,.48)}.evruni-public-cta strong{display:block;color:#fff;font-size:14px;margin-bottom:4px}.evruni-public-cta span{color:#7f8ba5;font-size:12px}.evruni-public-cta a{display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:12px;color:#fff;text-decoration:none;font-weight:800;font-size:12px;background:linear-gradient(135deg,#9b7cff,#5d3ce0);box-shadow:0 10px 28px rgba(108,76,255,.25);white-space:nowrap}
@media(max-width:900px){.evruni-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.evruni-mini-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.evruni-trend-grid{grid-template-columns:1fr 1fr}.evruni-trend-card.featured{grid-column:1/-1}}
@media(max-width:600px){.evruni-public-sections{width:calc(100% - 24px);padding-bottom:55px}.evruni-public-head h2{font-size:25px}.evruni-feature-grid,.evruni-mini-grid,.evruni-trend-grid{grid-template-columns:1fr}.evruni-feature-card{min-height:0}.evruni-creator-section,.evruni-trend-section{padding:18px;margin-top:34px}.evruni-trend-card.featured{grid-column:auto}.evruni-public-cta{display:block}.evruni-public-cta a{margin-top:14px;width:100%}}
</style>
CSS;

    $sections = <<<'HTML'
<section class="evruni-public-sections" id="evruni-public-sections">
  <div class="evruni-public-head">
    <div class="evruni-public-kicker">EVRUNI AI WORKSPACE</div>
    <h2>Всё необходимое — в одном месте</h2>
    <p>AI-инструменты для общения, создания контента, исследований, работы с файлами и быстрых экспериментов.</p>
  </div>
  <div class="evruni-feature-grid">
    <article class="evruni-feature-card"><div class="evruni-feature-icon">✦</div><h3>AI Chat</h3><p>Общайся с AI, решай задачи, пиши тексты и получай ответы в одном рабочем пространстве.</p><a class="evruni-feature-link" href="/register.php">Попробовать →</a></article>
    <article class="evruni-feature-card"><div class="evruni-feature-icon">✧</div><h3>Image & Video</h3><p>Создавай изображения и видео, превращая идеи в готовый визуальный контент.</p><a class="evruni-feature-link" href="/register.php">Создать →</a></article>
    <article class="evruni-feature-card"><div class="evruni-feature-icon">⌕</div><h3>Research</h3><p>Исследуй темы, собирай информацию и превращай сложные вопросы в понятные результаты.</p><a class="evruni-feature-link" href="/register.php">Исследовать →</a></article>
    <article class="evruni-feature-card"><div class="evruni-feature-icon">▤</div><h3>Documents</h3><p>Работай с документами и файлами прямо внутри AI-workspace.</p><a class="evruni-feature-link" href="/register.php">Работать с файлами →</a></article>
  </div>

  <section class="evruni-creator-section">
    <div class="evruni-public-head"><div class="evruni-public-kicker">EVRUNI CREATOR</div><h2>Инструменты для стримеров</h2><p>Evruni не заменяет Twitch, YouTube или Kick — он добавляет AI-инструменты поверх существующей платформы.</p></div>
    <div class="evruni-mini-grid">
      <article class="evruni-mini-card"><h3>🎥 Creator Tools</h3><p>AI Chat Bot, модерация, Voice Cleaner, Smart Alerts, клипы и Stream Analytics.</p></article>
      <article class="evruni-mini-card"><h3>💜 Прямые донаты</h3><p>Зритель поддерживает стримера через его платёжный сервис. Evruni не хранит деньги.</p></article>
      <article class="evruni-mini-card"><h3>🛡️ AI Film Guard</h3><p>Поиск фильма в базе, таймлайн сцен и автоматические действия blur/mute.</p></article>
      <article class="evruni-mini-card"><h3>⚡ Stream Copilot</h3><p>Помощь во время эфира только по событиям — без постоянного тяжёлого AI-анализа.</p></article>
    </div>
    <div class="evruni-public-cta"><div><strong>Попробуй инструменты для стримера</strong><span>Creator Tools уже доступны в Evruni.</span></div><a href="/register.php">Создать аккаунт</a></div>
  </section>

  <section class="evruni-trend-section">
    <div class="evruni-public-head"><div class="evruni-public-kicker">EVRUNI TREND LAB</div><h2>Тренды превращаются в идеи</h2><p>Отслеживай растущие темы и используй AI, чтобы понять, почему они набирают внимание и что можно создать на их основе.</p></div>
    <div class="evruni-trend-grid">
      <article class="evruni-trend-card featured"><div class="evruni-trend-label">↗ Live signal</div><h3>Что сейчас набирает популярность</h3><p>Trend Lab показывает сигналы роста интереса и помогает находить темы раньше, чем они становятся очевидными.</p></article>
      <article class="evruni-trend-card"><div class="evruni-trend-label">✦ Analyze</div><h3>Разбор тренда</h3><p>Пойми контекст, причины роста и связанные темы.</p></article>
      <article class="evruni-trend-card"><div class="evruni-trend-label">⚡ Create</div><h3>Идеи для контента</h3><p>Преврати тренд в тему для стрима, видео, поста или AI-проекта.</p></article>
    </div>
    <div class="evruni-public-cta"><div><strong>Открой Trend Lab внутри Evruni</strong><span>Тренды, AI и Creator Tools — в одном пространстве.</span></div><a href="/register.php">Начать бесплатно</a></div>
  </section>
</section>
HTML;

    if (strpos($html, '</main>') !== false) {
        $html = str_replace('</main>', $css . $sections . '</main>', $html, 1);
    } elseif (strpos($html, '</body>') !== false) {
        $html = str_replace('</body>', $css . $sections . '</body>', $html, 1);
    }
    return $html;
});
