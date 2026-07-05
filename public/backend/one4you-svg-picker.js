(function () {
  const trigger = '##svg';
  const labels = {
    outline: 'Outline',
    filled: 'Filled',
    custom: 'Custom',
  };

  let modal;
  let searchInput;
  let grid;
  let activeSource = 'outline';
  let icons = null;
  let target = null;
  let dismissedTrigger = null;
  let openSequence = 0;
  let listenersReady = false;
  let tinyMceListenerReady = false;
  let tinyMceTimer = null;

  function boot() {
    bindGlobalListeners();
    setupTinyMce();
  }

  function bindGlobalListeners() {
    if (listenersReady) {
      return;
    }

    listenersReady = true;
    document.addEventListener('input', handleNativeInput, true);
    document.addEventListener('keyup', handleNativeKeyup, true);
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
        closeModal();
      }
    });

    document.addEventListener('turbo:load', setupTinyMce);
    document.addEventListener('turbo:render', setupTinyMce);
    document.addEventListener('contao--tinymce:editor-loaded', event => {
      bindTinyEditor(event.detail?.content);
    });
  }

  function setupTinyMce() {
    if (!window.tinymce) {
      if (!tinyMceTimer) {
        tinyMceTimer = window.setTimeout(() => {
          tinyMceTimer = null;
          setupTinyMce();
        }, 500);
      }

      return;
    }

    if (!tinyMceListenerReady) {
      tinyMceListenerReady = true;
      window.tinymce.on('AddEditor', event => bindTinyEditor(event.editor));
    }

    getTinyEditors().forEach(bindTinyEditor);
  }

  function getTinyEditors() {
    const editors = window.tinymce?.editors;

    if (!editors) {
      return [];
    }

    if (typeof editors.forEach === 'function') {
      return Array.from(editors);
    }

    return Object.values(editors);
  }

  function bindTinyEditor(editor) {
    if (!editor) {
      return;
    }

    if (editor.one4youSvgPickerReady) {
      return;
    }

    editor.one4youSvgPickerReady = true;
    editor.on('keyup input change NodeChange', () => {
      window.setTimeout(() => handleTinyInput(editor), 0);
    });
  }

  function handleNativeInput(event) {
    handleNativeField(event.target);
  }

  function handleNativeKeyup(event) {
    if (event.key.length === 1 || event.key === 'Backspace' || event.key === 'Delete') {
      handleNativeField(event.target);
    }
  }

  function handleNativeField(element) {
    if (!(element instanceof HTMLTextAreaElement) && !(element instanceof HTMLInputElement)) {
      return;
    }

    if (modal?.classList.contains('is-open')) {
      return;
    }

    if (modal?.contains(element)) {
      return;
    }

    syncDismissedField(element);

    if (element.selectionStart === null) {
      return;
    }

    const cursor = element.selectionStart;

    if (element.value.slice(0, cursor).endsWith(trigger)) {
      const nextTarget = {
        type: 'field',
        element,
        start: cursor - trigger.length,
        end: cursor,
      };

      if (isDismissedTarget(nextTarget)) {
        return;
      }

      dismissedTrigger = null;
      target = nextTarget;
      openModal();
    }
  }

  function handleTinyInput(editor) {
    if (modal?.classList.contains('is-open')) {
      return;
    }

    syncDismissedTiny(editor);

    const match = detectTinyTrigger(editor);

    if (!match) {
      return;
    }

    const nextTarget = {
      type: 'tinymce',
      editor,
      node: match.node,
      start: match.start,
      end: match.end,
    };

    if (isDismissedTarget(nextTarget)) {
      return;
    }

    dismissedTrigger = null;
    target = nextTarget;
    openModal();
  }

  function detectTinyTrigger(editor) {
    if (editor.removed) {
      return null;
    }

    let range;
    let body;

    try {
      range = editor.selection.getRng();
      body = editor.getBody();
    } catch (error) {
      return null;
    }

    const textPosition = findTextPositionBeforeCaret(range, body);

    if (!textPosition) {
      return null;
    }

    const text = textPosition.node.textContent || '';

    if (!text.slice(0, textPosition.offset).endsWith(trigger)) {
      return null;
    }

    return {
      node: textPosition.node,
      start: textPosition.offset - trigger.length,
      end: textPosition.offset,
    };
  }

  function findTextPositionBeforeCaret(range, root) {
    if (!range) {
      return null;
    }

    if (range.startContainer.nodeType === Node.TEXT_NODE && range.startOffset > 0) {
      return {
        node: range.startContainer,
        offset: range.startOffset,
      };
    }

    const directNode = findLastTextNode(range.startContainer.childNodes?.[range.startOffset - 1] || null);

    if (directNode) {
      return {
        node: directNode,
        offset: directNode.textContent.length,
      };
    }

    const previousNode = findPreviousTextNode(range.startContainer, root);

    if (!previousNode) {
      return null;
    }

    return {
      node: previousNode,
      offset: previousNode.textContent.length,
    };
  }

  function findLastTextNode(node) {
    if (!node) {
      return null;
    }

    if (node.nodeType === Node.TEXT_NODE) {
      return node;
    }

    for (let i = node.childNodes.length - 1; i >= 0; i -= 1) {
      const textNode = findLastTextNode(node.childNodes[i]);

      if (textNode) {
        return textNode;
      }
    }

    return null;
  }

  function findPreviousTextNode(node, root) {
    let current = node;

    while (current && current !== root) {
      let sibling = current.previousSibling;

      while (sibling) {
        const textNode = findLastTextNode(sibling);

        if (textNode) {
          return textNode;
        }

        sibling = sibling.previousSibling;
      }

      current = current.parentNode;
    }

    return null;
  }

  async function openModal() {
    const sequence = ++openSequence;

    ensureModal();
    modal.classList.add('is-open');
    searchInput.value = '';

    if (!icons) {
      grid.innerHTML = '<p class="one4you-svg-picker__empty">Icons werden geladen ...</p>';
      const response = await fetch(window.One4YouSvgPickerConfig?.endpoint || '/contao/one4you/svg-icons', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const data = await response.json();
      icons = data.icons || {};
    }

    if (sequence !== openSequence || !modal.classList.contains('is-open')) {
      return;
    }

    setActiveSource(activeSource);
    searchInput.focus();
  }

  function closeModal({ restoreFocus = true } = {}) {
    const previousTarget = target;
    openSequence += 1;
    modal?.classList.remove('is-open');
    dismissedTrigger = restoreFocus && previousTarget ? createDismissedTarget(previousTarget) : null;
    target = null;

    if (restoreFocus && previousTarget) {
      window.setTimeout(() => restoreTargetFocus(previousTarget), 0);
    }
  }

  function createDismissedTarget(sourceTarget) {
    return {
      type: sourceTarget.type,
      element: sourceTarget.element,
      editor: sourceTarget.editor,
      node: sourceTarget.node,
      start: sourceTarget.start,
      end: sourceTarget.end,
    };
  }

  function isDismissedTarget(nextTarget) {
    if (!dismissedTrigger || dismissedTrigger.type !== nextTarget.type) {
      return false;
    }

    if (nextTarget.type === 'field') {
      return dismissedTrigger.element === nextTarget.element
        && dismissedTrigger.start === nextTarget.start
        && dismissedTrigger.end === nextTarget.end;
    }

    return dismissedTrigger.editor === nextTarget.editor
      && dismissedTrigger.node === nextTarget.node
      && dismissedTrigger.start === nextTarget.start
      && dismissedTrigger.end === nextTarget.end;
  }

  function syncDismissedField(element) {
    if (!dismissedTrigger || dismissedTrigger.type !== 'field' || dismissedTrigger.element !== element) {
      return;
    }

    if (element.value.slice(dismissedTrigger.start, dismissedTrigger.end) !== trigger) {
      dismissedTrigger = null;
    }
  }

  function syncDismissedTiny(editor) {
    if (!dismissedTrigger || dismissedTrigger.type !== 'tinymce' || dismissedTrigger.editor !== editor) {
      return;
    }

    if (
      !dismissedTrigger.node.isConnected
      || (dismissedTrigger.node.textContent || '').slice(dismissedTrigger.start, dismissedTrigger.end) !== trigger
    ) {
      dismissedTrigger = null;
    }
  }

  function restoreTargetFocus(previousTarget) {
    if (previousTarget.type === 'field') {
      previousTarget.element.focus();
      previousTarget.element.setSelectionRange(previousTarget.end, previousTarget.end);

      return;
    }

    if (previousTarget.type !== 'tinymce' || previousTarget.editor.removed || !previousTarget.node.isConnected) {
      return;
    }

    try {
      const range = previousTarget.editor.dom.doc.createRange();
      range.setStart(previousTarget.node, previousTarget.end);
      range.collapse(true);
      previousTarget.editor.selection.setRng(range);
      previousTarget.editor.focus();
    } catch (error) {
      // The editor can be replaced by Turbo while the modal is open.
    }
  }

  function ensureModal() {
    if (modal) {
      return;
    }

    modal = document.createElement('div');
    modal.className = 'one4you-svg-picker';
    modal.innerHTML = `
      <div class="one4you-svg-picker__dialog" role="dialog" aria-modal="true" aria-label="SVG auswählen">
        <div class="one4you-svg-picker__header">
          <h2 class="one4you-svg-picker__title">SVG auswählen</h2>
          <button class="one4you-svg-picker__close" type="button" aria-label="Schließen">&times;</button>
        </div>
        <div class="one4you-svg-picker__tabs" role="tablist"></div>
        <div class="one4you-svg-picker__toolbar">
          <input class="tl_text one4you-svg-picker__search" type="search" placeholder="Suchen">
        </div>
        <div class="one4you-svg-picker__body">
          <div class="one4you-svg-picker__grid"></div>
        </div>
      </div>
    `;

    document.body.append(modal);
    searchInput = modal.querySelector('.one4you-svg-picker__search');
    grid = modal.querySelector('.one4you-svg-picker__grid');

    modal.querySelector('.one4you-svg-picker__close').addEventListener('click', closeModal);
    modal.addEventListener('click', event => {
      if (event.target === modal) {
        closeModal();
      }
    });

    const tabs = modal.querySelector('.one4you-svg-picker__tabs');
    Object.entries(labels).forEach(([source, label]) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'one4you-svg-picker__tab';
      button.textContent = label;
      button.addEventListener('click', () => setActiveSource(source));
      tabs.append(button);
    });

    searchInput.addEventListener('input', renderGrid);
  }

  function setActiveSource(source) {
    activeSource = source;
    modal.querySelectorAll('.one4you-svg-picker__tab').forEach(button => {
      button.classList.toggle('is-active', button.textContent === labels[source]);
    });
    renderGrid();
  }

  function renderGrid() {
    const query = searchInput.value.trim().toLowerCase();
    const sourceIcons = icons?.[activeSource] || [];
    const filtered = query
      ? sourceIcons.filter(icon => icon.name.toLowerCase().includes(query))
      : sourceIcons;

    if (!filtered.length) {
      grid.innerHTML = '<p class="one4you-svg-picker__empty">Keine Icons gefunden.</p>';
      return;
    }

    const fragment = document.createDocumentFragment();
    filtered.forEach(icon => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'one4you-svg-picker__item';
      button.innerHTML = `
        <span class="one4you-svg-picker__preview">${icon.svg}</span>
        <span class="one4you-svg-picker__name"></span>
      `;
      button.querySelector('.one4you-svg-picker__name').textContent = icon.name;
      button.addEventListener('click', () => insertIcon(icon.insertTag));
      fragment.append(button);
    });

    grid.replaceChildren(fragment);
  }

  function insertIcon(insertTag) {
    if (!target) {
      closeModal();
      return;
    }

    if (target.type === 'field') {
      const value = target.element.value;
      target.element.value = value.slice(0, target.start) + insertTag + value.slice(target.end);
      const cursor = target.start + insertTag.length;
      target.element.setSelectionRange(cursor, cursor);
      target.element.dispatchEvent(new Event('input', { bubbles: true }));
      target.element.focus();
    } else if (target.type === 'tinymce') {
      const range = target.editor.dom.doc.createRange();
      range.setStart(target.node, target.start);
      range.setEnd(target.node, target.end);
      target.editor.selection.setRng(range);
      target.editor.insertContent(insertTag);
      target.editor.focus();
    }

    closeModal({ restoreFocus: false });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
