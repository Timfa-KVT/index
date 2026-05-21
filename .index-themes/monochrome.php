<?php
return [
  'id' => 'monochrome',
  'label' => 'Monochrome',
  'isDefault' => false,
  'logo' => 'logo_website_black.png',
  'showThumbnail' => false,
  'showTopBar' => true,
  'showTitlebar' => false,
  'expandDescriptionByDefault' => true,
  'useLoadingAnimation' => false,
  'transformDescriptionHeading' => true,
  'css' => <<<'CSS'
:root {
  --section-label-color: rgba(0, 0, 0, 0.78);
  --section-separator-color: rgba(0, 0, 0, 0.24);
}

body {
  filter: saturate(0);
}

a.card {
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.16);
  background: #ffffff;
  min-height: 220px;
  display: flex;
  flex-direction: column;
}

a.card:hover {
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2);
  border-color: rgba(0, 0, 0, 0.3);
}

.card-topbar {
  display: block;
  height: 8px;
  flex: 0 0 8px;
  background: #000000;
}

a.card.is-restricted {
  background: #d8d8d8;
  border-color: #b2b2b2;
}

a.card.is-restricted .card-topbar {
  background: #8f8f8f;
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
  color: rgba(0, 0, 0, 0.72);
  display: block;
  overflow: visible;
}

.desc h3 {
  margin: 0 0 10px;
  font-size: 23px;
  line-height: 1.2;
  color: #1a1a1a;
  font-weight: 800;
}

.desc > :last-child {
  margin-bottom: 0;
}
CSS,
  'js' => '',
];
