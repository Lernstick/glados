// get the plugin object from videojs
var Plugin = videojs.getPlugin('plugin');
var VjsButton = videojs.getComponent('Button');

/**
 * next button
 */
class nextItemButton extends VjsButton {
  constructor(player, options) {
    super(player, options);
  }

  /**
   * @inheritdoc
   */
  buildCSSClass() {
    return 'vjs-next-item-control vjs-control vjs-button';
  }

  /**
   * @inheritdoc
   */
  handleClick(event) {
    this.player().playlist().next();
  }
}

/**
 * previous button
 */
class previousItemButton extends VjsButton {
  constructor(player, options) {
    super(player, options);
  }

  /**
   * @inheritdoc
   */
  buildCSSClass() {
    return 'vjs-previous-item-control vjs-control vjs-button';
  }

  /**
   * @inheritdoc
   */
  handleClick(event) {
    this.player().playlist().prev();
  }
}

nextItemButton.prototype.controlText_ = 'Next';
previousItemButton.prototype.controlText_ = 'Previous';
videojs.registerComponent('nextItemButton', nextItemButton);
videojs.registerComponent('previousItemButton', previousItemButton);

/**
 * playlist for videojs
 *
 * @property active the active playlist item
 * @property autoplay whether to automatically start playing when the source is changed
 * @property options the options array
 */
class playlist extends Plugin {
  constructor(player, options) {
    super(player, options);

    this.active = 0;
    this.autoplay = false;
    this.options = options;

    player.on('ready', function() {
        var nextButton = this.player_.getChild('controlBar').addChild('nextItemButton', {}, 1);
        var prevButton = this.player_.getChild('controlBar').addChild('previousItemButton', {}, 0);
    });

    player.on('loadeddata', function() {
        for (var i = 0; i < options.playlist.length; i++) {
            if (options.playlist[i].master === player.currentSrc().split(/[\\/]/).pop()) {
                this.player_.playlist().active = i;
            }
        }
        if (this.player_.playlist().autoplay) {
            player.play();
            this.player_.playlist().autoplay = false;
        }
    });

    player.on('ended', function() {
        this.player_.playlist().autoplay = true;
        this.player_.playlist().next();
    });
  }

  /**
   * Load the previous playlist item
   *
   * @return void
   */
  prev() {
    if (typeof this.options.playlist[this.active - 1] !== 'undefined') {
        this.player.src({
            type: 'application/x-mpegURL',
            src: this.options.playlist[this.active - 1].url
        });
    }
  }

  /**
   * Load the next playlist item
   *
   * @return void
   */
  next() {
    if (typeof this.options.playlist[this.active + 1] !== 'undefined') {
        this.player.src({
            type: 'application/x-mpegURL',
            src: this.options.playlist[this.active + 1].url
        });
    }
  }

}

videojs.registerPlugin('playlist', playlist);
