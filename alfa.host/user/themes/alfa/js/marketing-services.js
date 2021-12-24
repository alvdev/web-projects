const tabs = document.querySelectorAll('.services ul#tabs li a');
const tabsContent = document.querySelectorAll('.services [id*=tabcontent]');

const activeTab = tabs.forEach(tab => {
  tab.addEventListener('click', (e, tabContentId) => {
    if (!tab.classList.contains('active')) {
      tabs.forEach(tab => {
        tab.classList.remove('active');
      });
      tab.classList.add('active');
    }

    tabsContent.forEach(tabContent => {
      console.log(tab.getAttribute('id'));
      if (!tabContent.getAttribute('id').includes(tab.getAttribute('id'))) {
        console.log('Working');
        tabsContent.forEach(tabContent => {
          tabContent.classList.add('hidden');
        });
        tabContent.classList.remove('hidden');
      }
    });
  });
});

// console.log(activeTab);

// const activeTabContent = tabs.forEach(tab => {
//   const tabActiveId = tab.getAttribute('id');
//   console.log(tabActiveId);
// });

// console.log(activeTabContentId);
