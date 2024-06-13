(() => {
  const popups = document.querySelectorAll('.nine3-popup');

  if (!popups[0]) {
    return;
  }

  /**
   * Triggers XMLHttpReq and leaves a cookie via a php script
   * @param {int} popupId 
   */
  const triggerCookieDrop = popupId => {
    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
      if (this.readyState == 4) {
        const response = JSON.parse(xhttp.response);
        console.log(response);
      }
    };

    xhttp.open('GET', `${nine3popup.ajax_url}?action=nine3-popup&nonce=${nine3popup.nonce}&popup_id=${popupId}`);
    xhttp.send();
  };

  /**
   * Click event callback, closes popup, triggers cookie drop.
   * @param {HTMLElement} popup 
   */
  const closePopup = popup => {
    let popupId = popup.getAttribute('id');
    popupId = popupId.replace('popup-id-', '');
    triggerCookieDrop(popupId);
    popup.parentNode.removeChild(popup);
  };

  popups.forEach(popup => {
    // Delay the popup if has data attr.
    const timeDelay = popup.dataset.delay;
    if (timeDelay) {
      const mainClass = popup.classList[0];
      const allClasses = popup.classList.value;

      // Remove all but the main class to prevent css animations and hide.
      popup.style.display = 'none';
      popup.className = mainClass;

      // Re-add classes and show popup after delay.
      setTimeout(() => {
        popup.style.display = 'block';
        popup.className = allClasses;
      }, timeDelay * 1000);
    }

    // Close button click event.
    const close = popup.querySelector('.nine3-popup__close');
    close.addEventListener('click', () => {
      closePopup(popup);
    });

    popup.addEventListener('click', (e) => {
      if (e.target !== e.currentTarget) {
        return;
      }
      closePopup(popup);
    });

    const popupWrapper = popup.querySelector('.nine3-popup__wrapper');
    const popupContent = popup.querySelector('.nine3-popup__content');
    const wrapperHeight = popupWrapper.clientHeight;
    const contentHeight = popupContent.clientHeight;
    if (wrapperHeight < contentHeight) {
      popupContent.style.alignSelf = 'unset';
    }
  });
})();