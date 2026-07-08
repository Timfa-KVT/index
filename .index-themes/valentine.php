<?php
return [
  'id' => 'valentine',
  'label' => 'Valentine',
  'isDefault' => false,
  'logo' => 'kvt_logo_pink.png',
  'showThumbnail' => false,
  'showTopBar' => true,
  'showTitlebar' => false,
  'expandDescriptionByDefault' => true,
  'useLoadingAnimation' => false,
  'transformDescriptionHeading' => true,
  'css' => <<<'CSS'
:root {
  --section-label-color: rgba(190, 24, 93, 0.82);
  --section-separator-color: rgba(219, 39, 119, 0.28);
}

body::before {
  background:
    radial-gradient(900px 420px at 18% 0%, rgba(244, 63, 94, 0.14), transparent 58%),
    radial-gradient(920px 540px at 88% 8%, rgba(236, 72, 153, 0.12), transparent 62%),
    radial-gradient(900px 520px at 50% 100%, rgba(251, 113, 133, 0.1), transparent 65%);
}

.valentine-hearts {
  position: fixed;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
  z-index: 0;
}

.valentine-heart {
  position: absolute;
  top: 0;
  left: var(--heart-left, 50%);
  width: var(--heart-size, 32px);
  height: var(--heart-size, 32px);
  opacity: var(--heart-opacity, 0.12);
  background: url('.index-themes/images/heart.png') center / contain no-repeat;
  will-change: transform;
  animation: valentine-fall var(--fall-duration, 20s) linear infinite;
}

@keyframes valentine-fall {
  0% {
    transform: translateY(-100%);
  }

  100% {
    transform: translateY(100vh);
  }
}

.wrap {
  z-index: 1;
}

a.card {
  box-shadow: 0 8px 22px rgba(190, 24, 93, 0.1);
  background: rgba(255, 255, 255, 0.96);
  min-height: 220px;
  display: flex;
  flex-direction: column;
}

a.card::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  background:
    url('.index-themes/images/heart.png') var(--card-heart-x, 72%) var(--card-heart-y, 78%) / var(--card-heart-size, 72px) auto no-repeat;
  opacity: var(--card-heart-opacity, 0.09);
}

a.card:hover {
  box-shadow: 0 14px 30px rgba(190, 24, 93, 0.16);
  border-color: rgba(190, 24, 93, 0.2);
}

.card-topbar {
  display: block;
  height: 8px;
  flex: 0 0 8px;
  background: #ec4899;
  position: relative;
  z-index: 1;
}

a.card.is-restricted .card-topbar {
  background: #be123c;
}

.content {
  padding: 16px 16px 18px;
  transform: none;
  position: relative;
  max-height: none;
  overflow: visible;
  border-top: none;
  background: transparent;
  flex: 1 1 auto;
  z-index: 1;
}

.desc {
  color: rgba(76, 5, 25, 0.78);
  display: block;
  overflow: visible;
}

.desc h3 {
  margin: 0 0 10px;
  font-size: 23px;
  line-height: 1.2;
  color: #be185d;
  font-weight: 800;
}

.desc > :last-child {
  margin-bottom: 0;
}

.theme-option.is-active {
  background: #fce7f3;
  border-color: #f9a8d4;
  color: #9d174d;
}
CSS,
  'js' => <<<'JS'
(function ()
{
  const theme = document.body.getAttribute('data-theme');
  if (theme !== 'valentine') return;

  const HEART_COUNT = 20;

  const rand = (min, max) => min + Math.random() * (max - min);

  const container = document.createElement('div');
  container.className = 'valentine-hearts';
  container.setAttribute('aria-hidden', 'true');
  document.body.appendChild(container);

  for (let i = 0; i < HEART_COUNT; i++)
  {
    const heart = document.createElement('span');
    heart.className = 'valentine-heart';

    const size = rand(20, 58);
    const duration = rand(16, 32);
    const left = rand(-2, 98);
    const opacity = rand(0.06, 0.17);

    heart.style.setProperty('--heart-size', `${size}px`);
    heart.style.setProperty('--fall-duration', `${duration}s`);
    heart.style.setProperty('--heart-left', `${left}%`);
    heart.style.setProperty('--heart-opacity', opacity.toFixed(3));
    heart.style.animationDelay = `${-rand(0, duration)}s`;

    container.appendChild(heart);
  }

  document.querySelectorAll('a.card').forEach((card) =>
  {
    const size = rand(44, 108);
    const x = rand(4, 88);
    const y = rand(8, 92);
    const opacity = rand(0.05, 0.13);

    card.style.setProperty('--card-heart-size', `${size}px`);
    card.style.setProperty('--card-heart-x', `${x}%`);
    card.style.setProperty('--card-heart-y', `${y}%`);
    card.style.setProperty('--card-heart-opacity', opacity.toFixed(3));
  });
})();
JS,
];
