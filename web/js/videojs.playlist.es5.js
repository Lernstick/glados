// get the plugin object from videojs
var Plugin = videojs.getPlugin('plugin');
var VjsButton = videojs.getComponent('Button');

/**
 * next button
 */
var nextItemButton = videojs.extend(VjsButton, {
  constructor: function(player, options) {
    VjsButton.call(this, player, options);
  },

  buildCSSClass: function() {
    return 'vjs-next-item-control vjs-control vjs-button';
  },

  handleClick: function(event) {
    this.player().playlist().next();
  }
});

/**
 * previous button
 */
var previousItemButton = videojs.extend(VjsButton, {
  constructor: function(player, options) {
    VjsButton.call(this, player, options);
  },

  buildCSSClass: function() {
    return 'vjs-previous-item-control vjs-control vjs-button';
  },

  handleClick: function(event) {
    this.player().playlist().next();
  }
});

nextItemButton.prototype.controlText_ = 'Next item in playlist';
previousItemButton.prototype.controlText_ = 'Previous item in playlist';
videojs.registerComponent('nextItemButton', nextItemButton);
videojs.registerComponent('previousItemButton', previousItemButton);

/**
 * playlist for videojs
 */
var playlist = videojs.extend(Plugin, {

  constructor: function(player, options) {
    Plugin.call(this, player, options);

    // property for the active element in the playlist
    this.active_ = 0;
    //this.autoplay_ = false;
    if (typeof options.active === 'undefined') {
        options.active = 0;
    }

    if (typeof options.autoplay === 'undefined') {
        options.autoplay = false;
    }
    //this.options = options;
    //this.player = player;

    player.on('ready', function() {
        var nextButton = player.getChild('controlBar').addChild('nextItemButton', {}, 1);
        var prevButton = player.getChild('controlBar').addChild('previousItemButton', {}, 0);
    });

    player.on('loadeddata', function() {
        for (var i = 0; i < options.playlist.length; i++) {
            if (options.playlist[i].master === player.currentSrc().split(/[\\/]/).pop()) {
                this.active_ = i;
                options.active = i;
            }
        }
        if (options.autoplay) {
            player.play();
            options.autoplay = false;
        }
    });

    player.on('ended', function() {
        options.autoplay = true;
        console.log("player.playlist()", player.playlist());
        player.getChild('playlist').next();
    });

  },

  prev: function() {
    if (typeof this.options.playlist[options.active - 1] !== 'undefined') {
        this.player.src({
            type: 'application/x-mpegURL',
            src: this.options.playlist[options.active - 1].url
        });
    }
  },

  next: function() {
    if (typeof options.playlist[options.active + 1] !== 'undefined') {
        player.src({
            type: 'application/x-mpegURL',
            src: options.playlist[options.active + 1].url
        });
    }
  }
});

videojs.registerPlugin('playlist', playlist);



