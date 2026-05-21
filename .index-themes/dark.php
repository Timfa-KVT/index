<?php
return [
  'id' => 'dark',
  'label' => 'Dark',
  'isDefault' => false,
  'logo' => 'logo_website_white.png',
  'showThumbnail' => false,
  'showTopBar' => true,
  'showTitlebar' => false,
  'expandDescriptionByDefault' => true,
  'useLoadingAnimation' => false,
  'transformDescriptionHeading' => true,
  'css' => <<<'CSS'
:root {
  --section-label-color: rgba(255, 255, 255, 0.84);
  --section-separator-color: rgba(255, 255, 255, 0.24);
}

body {
  background: #000000;
  color: #f0f0f0;
}

body::before {
  background:
    radial-gradient(900px 420px at 18% 0%, rgba(255, 255, 255, 0.08), transparent 58%),
    radial-gradient(920px 540px at 90% 100%, rgba(255, 255, 255, 0.04), transparent 66%);
}

.theme-switcher-button {
  color: rgba(255, 255, 255, 0.75);
}

.theme-switcher-button:hover,
.theme-switcher-button:focus-visible {
  color: rgba(255, 255, 255, 0.95);
}

.theme-switcher-panel {
  border-color: rgba(255, 255, 255, 0.35);
  background: rgba(8, 8, 8, 0.92);
}

.theme-switcher-title {
  color: rgba(255, 255, 255, 0.75);
}

.theme-option {
  border-color: rgba(255, 255, 255, 0.22);
  background: rgba(25, 25, 25, 0.96);
  color: rgba(255, 255, 255, 0.9);
}

.theme-option:hover,
.theme-option:focus-visible {
  background: rgba(48, 48, 48, 0.96);
  border-color: rgba(255, 255, 255, 0.5);
}

.theme-option.is-active {
  background: rgba(255, 255, 255, 0.14);
  border-color: rgba(255, 255, 255, 0.72);
  color: #ffffff;
}

a.card {
  box-shadow:
    0 16px 38px rgba(0, 0, 0, 0.6),
    inset 0 0 0 1px rgba(255, 255, 255, 0.3),
    inset 0 12px 24px rgba(255, 255, 255, 0.05);
  background: #050505;
  border-color: rgba(255, 255, 255, 0.68);
  min-height: 220px;
  display: flex;
  flex-direction: column;
}

a.card:hover {
  box-shadow:
    0 20px 44px rgba(0, 0, 0, 0.7),
    inset 0 0 0 1px rgba(255, 255, 255, 0.45),
    inset 0 16px 26px rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.9);
}

.card-topbar {
  display: block;
  height: 8px;
  flex: 0 0 8px;
  background: #ffffff;
}

a.card.is-restricted {
  background: #232323;
  border-color: rgba(130, 130, 130, 0.92);
}

a.card.is-restricted .card-topbar {
  background: #4a4a4a;
}

.content {
  padding: 16px 16px 18px;
  transform: none;
  position: static;
  max-height: none;
  overflow: visible;
  border-top: none;
  background: transparent;
  flex: 1 1 auto;
}

.desc {
  color: rgba(255, 255, 255, 0.8);
  display: block;
  overflow: visible;
}

.desc h3 {
  margin: 0 0 10px;
  font-size: 23px;
  line-height: 1.2;
  color: #ffffff;
  font-weight: 800;
}

.desc > :last-child {
  margin-bottom: 0;
}
CSS,
  'js' => '',
];
