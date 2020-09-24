// get the plugin object from videojs
var Plugin = videojs.getPlugin('plugin');

/**
 * hotkeys plugin for videojs.
 *
 * @property bool visible whether the player is in the visible part of the user's screen
 */
class hotkeys extends Plugin {
  constructor(player, options) {
    super(player, options);

    // make the object visible by default
    this.visible = true;

    player.on('ready', function() {

      let playerObj = this.player_;

      // this is the target which is observed
      var target = playerObj.el_;

      var observer = new IntersectionObserver(onIntersection, {
        root: null,   // default is the viewport
        threshold: .5 // percentage of the target visible area which will trigger "onIntersection"
      });

      // called when target is fully visible
      function onIntersection(entries, opts){
        entries.forEach(entry => {
          playerObj.hotkeys().visible = entry.intersectionRatio >= opts.thresholds[0];
        })
      }

      // provide the observer with a target
      observer.observe(target);

      document.addEventListener('keydown', function(event) {

        // only trigger events when the player is in the visible part of the user
        if (playerObj.hotkeys().visible) {
          if (playerObj.hotkeys().keypressed(event, options.toggleFullscreen)) {
            playerObj.hotkeys().toggleFullscreen();
          } else if (playerObj.hotkeys().keypressed(event, options.togglePause)) {
            playerObj.hotkeys().togglePause();
          } else if (playerObj.hotkeys().keypressed(event, options.plusShort)) {
            playerObj.hotkeys().deltaTime(+10);
          } else if (playerObj.hotkeys().keypressed(event, options.minusShort)) {
            playerObj.hotkeys().deltaTime(-10);
          } else if (playerObj.hotkeys().keypressed(event, options.plusMedium)) {
            playerObj.hotkeys().deltaTime(+60);
          } else if (playerObj.hotkeys().keypressed(event, options.minusMedium)) {
            playerObj.hotkeys().deltaTime(-60);
          } else if (playerObj.hotkeys().keypressed(event, options.plusLong)) {
            playerObj.hotkeys().deltaTime(+300);
          } else if (playerObj.hotkeys().keypressed(event, options.minusLong)) {
            playerObj.hotkeys().deltaTime(-300);
          }
        }

      });
    });

  }

  /**
   * Determine whether the keys corresponding to [[opt]] are pressed or not
   *
   * @param Event event the keydown event object
   * @param string opt the options element conaining the key to be pressed (ex: "Ctrl+Alt+ArrowRight")
   * @return bool whether the key(s) where pressed or not
   */
  keypressed(event, opt) {
    var ctrl = false;
    var alt = false;

    if (opt.includes("+")) {
      var keys = opt.split("+");
      ctrl = keys.includes('Ctrl') ? true : false;
      alt = keys.includes('Alt') ? true : false;
      var key = keys[keys.length - 1];
    } else {
      var key = opt;
    }

    return event.key === key && event.altKey === alt && event.ctrlKey === ctrl;
  }

  /**
   * Toggle the fullscreen of the player
   *
   * @return mixed
   * @see https://docs.videojs.com/docs/api/player.html#MethodsrequestFullscreen
   * @see https://docs.videojs.com/docs/api/player.html#MethodsexitFullscreen
   */
  toggleFullscreen() {
    return this.player.isFullscreen() ? this.player.exitFullscreen() : this.player.requestFullscreen();
  }

  /**
   * Jump the amount of [[delta]] seconds in the current playing source
   *
   * @param int delta the amount of seconds to shift (can be positive or negative)
   * @return void
   * @see https://docs.videojs.com/docs/api/player.html#MethodscurrentTime
   */
  deltaTime($delta) {
    this.player.currentTime(this.player.currentTime() + $delta);
  }

  /**
   * Toggle play/pause
   *
   * @return mixed
   * @see https://docs.videojs.com/docs/api/player.html#Methodsplay
   * @see https://docs.videojs.com/docs/api/player.html#Methodspause
   */
  togglePause() {
    return this.player.paused() ? this.player.play() : this.player.pause();
  }

}

videojs.registerPlugin('hotkeys', hotkeys);
