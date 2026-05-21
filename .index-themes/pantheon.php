<?php
return [
  'id' => 'pantheon',
  'label' => 'Pantheon',
  'isDefault' => false,
  'logo' => 'logo_website_pantheon.png',
  'showThumbnail' => true,
  'showTopBar' => false,
  'showTitlebar' => false,
  'expandDescriptionByDefault' => false,
  'useLoadingAnimation' => true,
  'transformDescriptionHeading' => false,
  'css' => <<<'CSS'
:root {
  --section-label-color: rgba(15, 23, 42, 0.72);
  --section-separator-color: rgba(15, 23, 42, 0.18);
}

a.card {
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
  transform: translateY(0) scale(1);
  transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease, border-color 0.16s ease;
}

a.card::after {
  content: "";
  display: block;
  height: 76px;
}

a.card:hover {
  transform: translateY(-4px) scale(1.01);
  background: var(--panel2);
  border-color: rgba(15, 23, 42, 0.14);
  box-shadow: 0 18px 55px rgba(15, 23, 42, 0.12);
}

a.card:active {
  transform: translateY(-2px) scale(1.006);
}

.thumbwrap {
  height: 300px;
  min-height: 300px;
}

img.thumb {
  filter: saturate(1.02) contrast(1.02);
  transform: scale(1);
  transition: transform 0.22s ease;
}

a.card:hover img.thumb {
  transform: scale(1.03);
}

a.card.is-restricted img.thumb {
  filter: grayscale(1) contrast(1.02);
}

.titlebar {
  position: absolute;
  left: 12px;
  right: 12px;
  bottom: 12px;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.55);
  background: rgba(255, 255, 255, 0.32);
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.14);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

@supports not ((backdrop-filter: blur(10px)) or (-webkit-backdrop-filter: blur(10px))) {
  .titlebar {
    background: rgba(255, 255, 255, 0.78);
  }
}

.title {
  margin: 0;
  font-size: 16px;
  font-weight: 900;
  letter-spacing: 0.01em;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: rgba(15, 23, 42, 0.92);
}

.pill {
  flex: 0 0 auto;
  font-size: 12px;
  color: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(15, 23, 42, 0.14);
  background: rgba(255, 255, 255, 0.55);
  padding: 4px 8px;
  border-radius: 999px;
}

.content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 3;
  border-top: 1px solid rgba(15, 23, 42, 0.1);
  background: rgba(255, 255, 255, 0.98);
  max-height: calc(100% - 8px);
  overflow: auto;
  transform: translateY(calc(100% - 76px));
  transition: transform 0.24s ease;
}

.desc {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

a.card:hover .content,
a.card:focus-visible .content {
  transform: translateY(0);
}

a.card:hover .desc,
a.card:focus-visible .desc {
  display: block;
  -webkit-line-clamp: initial;
  overflow: visible;
}

.grid.is-transitioning a.card {
  pointer-events: none;
}

.grid.is-transitioning a.card.is-exiting-left,
.grid.is-transitioning a.card.is-exiting-right {
  opacity: 0;
  transition: transform 0.42s ease, opacity 0.42s ease;
}

.grid.is-transitioning a.card.is-exiting-left {
  transform: translateX(-120px) scale(0.96);
}

.grid.is-transitioning a.card.is-exiting-right {
  transform: translateX(120px) scale(0.96);
}

.grid.is-transitioning a.card.is-selected {
  z-index: 999;
  box-shadow: 0 18px 55px rgba(15, 23, 42, 0.12);
  transition: transform 0.42s ease, box-shadow 0.42s ease;
}

.grid.is-transitioning a.card.is-selected.is-loading-slow .content {
  transform: translateY(0);
}

.grid.is-transitioning a.card.is-selected.is-loading-slow .desc {
  display: block;
  -webkit-line-clamp: initial;
  overflow: visible;
}

@media (max-width: 620px) {
  .thumbwrap {
    height: 190px;
  }
}
CSS,
  'js' => <<<'JS'
(function ()
{
  const theme = document.body.getAttribute('data-theme');
  if (theme !== 'pantheon') return;

  const cards = Array.from(document.querySelectorAll('.grid a.card'));
  if (!cards.length) return;

  let isNavigating = false;
  const OPENING_TEXT = 'Wordt geopend...';
  const LOADING_MESSAGES_BY_SECOND = {
    3: 'Gegevens laden kan even duren. Een moment geduld.',
    12: 'Nog bezig met laden...',
    25: 'Gegevens laden duurt langer dan verwacht.',
    35: 'Het programma is niet vastgelopen. Laden duurt alleen erg lang.',
    55: 'Bedankt voor uw geduld.'
  };

  const resetCardState = () =>
  {
    isNavigating = false;

    cards.forEach((card) =>
    {
      card.classList.remove('is-exiting-left', 'is-exiting-right', 'is-selected', 'is-loading-slow');
      card.style.transform = '';

      const desc = card.querySelector('.desc');
      if (!desc) return;

      desc.classList.remove('is-status-message');

      const originalDesc = desc.getAttribute('data-original-desc');
      if (originalDesc !== null)
      {
        desc.innerHTML = originalDesc;
      }
    });

    const grid = cards[0].closest('.grid');
    if (grid)
    {
      grid.classList.remove('is-transitioning');
    }
  };

  cards.forEach((card) =>
  {
    const desc = card.querySelector('.desc');
    if (desc)
    {
      desc.setAttribute('data-original-desc', desc.innerHTML);
    }
  });

  window.addEventListener('pageshow', () =>
  {
    resetCardState();
  });

  cards.forEach((card) =>
  {
    card.addEventListener('click', (event) =>
    {
      if (isNavigating)
      {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      isNavigating = true;

      const href = card.getAttribute('href');
      const grid = card.closest('.grid');
      if (!href || !grid) return;

      grid.classList.add('is-transitioning');

      const selectedRect = card.getBoundingClientRect();
      const selectedCenterX = selectedRect.left + (selectedRect.width / 2);
      const viewportCenterX = window.innerWidth / 2;
      const viewportCenterY = window.innerHeight / 2;
      const targetX = viewportCenterX - selectedCenterX;
      const targetY = viewportCenterY - (selectedRect.top + (selectedRect.height / 2));

      cards.forEach((otherCard) =>
      {
        if (otherCard === card) return;

        const otherRect = otherCard.getBoundingClientRect();
        const otherCenterX = otherRect.left + (otherRect.width / 2);
        otherCard.classList.add(otherCenterX < selectedCenterX ? 'is-exiting-left' : 'is-exiting-right');
      });

      const desc = card.querySelector('.desc');
      if (desc)
      {
        desc.textContent = OPENING_TEXT;
        desc.classList.add('is-status-message');
      }

      card.classList.add('is-selected');
      card.style.transform = `translate(${targetX}px, ${targetY}px) scale(1.02)`;

      Object.entries(LOADING_MESSAGES_BY_SECOND)
        .map(([seconds, text]) => [Number.parseInt(seconds, 10), text])
        .filter(([seconds, text]) => Number.isInteger(seconds) && seconds >= 0 && typeof text === 'string' && text !== '')
        .sort((a, b) => a[0] - b[0])
        .forEach(([seconds, text]) =>
        {
          window.setTimeout(() =>
          {
            if (!isNavigating || !desc) return;

            card.classList.add('is-loading-slow');
            desc.textContent = `${desc.textContent}\n${text}`;
          }, seconds * 1000);
        });

      window.setTimeout(() =>
      {
        window.location.href = href;
      }, 430);
    });
  });
})();
JS,
];
