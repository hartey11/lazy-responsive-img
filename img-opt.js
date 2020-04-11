'use strict';

require('intersection-observer');

import debounce from 'debounce';

const setSource = img => {
  const src = JSON.parse(img.getAttribute('data-src'));

  if (typeof src === 'string') {
    if (!img.getAttribute('src')) {
      img.setAttribute('src', src);
      img.removeAttribute('data-src');
    }
  } else if ('sizes' in src) {
    const width = (img.clientWidth > document.body.clientWidth ? document.body.clientWidth : img.clientWidth) * window.devicePixelRatio;
    let size = '';

    Object.entries(src.sizes).forEach(([key, attributes]) => {
      if (attributes.width >= width) {
        size = key;
      }
    });

    if (!size) {
      size = 'full'; //TODO: figure out better way get largest image if no sizes are big enough.
    }

    if (img.getAttribute('src') !== src.sizes[size].file) {
      img.setAttribute('src', src.sizes[size].file);
    }
  }
};

const intersectionObserver = new IntersectionObserver((entries, self) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      console.log('Image in view');
      setSource(entry.target);
      self.unobserve(entry.target);
    }
  });
}, {
  rootMargin: '0px 0px 50px 0px',
  threshold: 0,
});

const lazyLoad = () => {
  console.log('Lazy Load init');
  const images = document.querySelectorAll('img[data-src]');
  images.forEach(img => {
    intersectionObserver.observe(img);
  });
};

const mutationObserver = new MutationObserver(() => {
  console.log('A child node has been added or removed.');
  lazyLoad();
});

export default () => {
  // console.log('Images init');
  const ajaxContainers = document.querySelectorAll('.ajax-container');
  const debouncedHandler = debounce(() => {
    lazyLoad();
  }, 200);

  window.onload = () => {
    lazyLoad();
    ajaxContainers.forEach(container => {
      mutationObserver.observe(container, {childList: true, subtree: true});
    });
  };

  window.addEventListener('resize', () => {
    debouncedHandler();
  });
};

// export function wpFilter() {
//   console.log('wpFilters Working');
//   wp.hooks.addFilter(
//     'blocks.getSaveElement',
//     'namespace/modify-get-save-element',
//     modifyGetSaveElement
//   );
//
//   /**
//    * Wrap block in div.
//    *
//    * @param {object} element
//    * @param {object} blockType
//    * @param {object} attributes
//    *
//    * @return The element.
//    */
//   function modifyGetSaveElement(element, blockType) {
//     if (!element) {
//       return;
//     }
//
//
//
//     if (blockType.name === 'core/image') {
//       console.log(element);
//     //   return (
//     //     < div
//     //   className = 'quote-wrapper' >
//     //     {element}
//     //     < /div>
//     // )
//     }
//
//     return element;
//   }
// }
