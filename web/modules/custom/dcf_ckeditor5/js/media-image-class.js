(function (Drupal, once) {
  Drupal.behaviors.mediaImageClass = {
    attach(context) {
      once('media-image-class', '[data-image-class]', context).forEach((media) => {
        const image = media.querySelector('img');
        if (image && media.dataset.imageClass) {
          image.classList.add(media.dataset.imageClass);
        }
      });
    }
  };
})(Drupal, once);
