<?php
return [
  'id' => 'corporate',
  'label' => 'Corporate',
  'isDefault' => false,
  'showThumbnail' => false,
  'showTopBar' => true,
  'showTitlebar' => false,
  'expandDescriptionByDefault' => true,
  'useLoadingAnimation' => false,
  'transformDescriptionHeading' => true,
  'css' => <<<'CSS'
a.card {
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.96);
  min-height: 220px;
  display: flex;
  flex-direction: column;
}

a.card:hover {
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
  border-color: rgba(15, 23, 42, 0.16);
}

.card-topbar {
  display: block;
  height: 8px;
  flex: 0 0 8px;
  background: #0099cc;
}

a.card.is-restricted .card-topbar {
  background: #cf3b31;
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
  color: rgba(15, 23, 42, 0.78);
  display: block;
  overflow: visible;
}

.desc h3 {
  margin: 0 0 10px;
  font-size: 23px;
  line-height: 1.2;
  color: #00529B;
  font-weight: 800;
}

.desc > :last-child {
  margin-bottom: 0;
}
CSS,
  'js' => '',
];
