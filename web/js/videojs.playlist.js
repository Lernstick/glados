// get the plugin object from videojs
var Plugin = videojs.getPlugin('plugin');

/**
 * playlist fir videojs
 */
var playlist = videojs.extend(Plugin, {

  constructor: function(player, options) {
    Plugin.call(this, player, options);

    // property for the active element in the playlist
    var activeSrc = 0;

    player.on('loadeddata', function() {
        for (var i = 0; i < options.playlist.length; i++) {
            if (options.playlist[i].master === player.currentSrc().split(/[\\/]/).pop()) {
                activeSrc = i;
            }
        }
    });

    player.on('ended', function() {
        console.log("newsrc", options.playlist[activeSrc+1].master)
        player.src({type: 'application/x-mpegURL', src: options.playlist[activeSrc+1].master});
    });


  }
});

videojs.registerPlugin('playlist', playlist);
