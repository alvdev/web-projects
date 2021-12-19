const tabs = document.querySelectorAll('.services ul#tabs li a');
const tabsContent = document.querySelectorAll('.services div[id*=tabcontent]');

const activeTabContentId = tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    const tabId = tab.getAttribute('id');
    return tabId + 'content';
  });
});

console.log(activeTabContentId);
