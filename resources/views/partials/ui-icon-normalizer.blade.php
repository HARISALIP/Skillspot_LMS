<style>.ui-standard-icon{display:inline-block;min-width:1em;margin-inline:.08em;text-align:center}</style>
<script>
(() => {
  const icons = @json(config('ui_icons.emoji'));
  const entries = Object.entries(icons).sort((a, b) => b[0].length - a[0].length);
  const escape = value => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const pattern = new RegExp(entries.map(([emoji]) => escape(emoji)).join('|'), 'gu');
  const skip = element => element?.closest('script,style,textarea,input,select,[contenteditable="true"],[data-preserve-emoji]');

  function normalize(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) if (!skip(walker.currentNode.parentElement) && pattern.test(walker.currentNode.nodeValue)) nodes.push(walker.currentNode);
    pattern.lastIndex = 0;
    nodes.forEach(node => {
      const fragment = document.createDocumentFragment();
      let cursor = 0;
      node.nodeValue.replace(pattern, (emoji, offset) => {
        fragment.append(node.nodeValue.slice(cursor, offset));
        const icon = document.createElement('i');
        icon.className = `ui-standard-icon fa-solid ${icons[emoji]}`;
        icon.setAttribute('aria-hidden', 'true');
        fragment.append(icon);
        cursor = offset + emoji.length;
        return emoji;
      });
      fragment.append(node.nodeValue.slice(cursor));
      node.replaceWith(fragment);
    });
    pattern.lastIndex = 0;
  }

  normalize(document.body);
  new MutationObserver(records => records.forEach(record => {
    record.addedNodes.forEach(node => normalize(node.nodeType === Node.TEXT_NODE ? node.parentNode : node));
  })).observe(document.body, { childList: true, subtree: true });
})();
</script>
