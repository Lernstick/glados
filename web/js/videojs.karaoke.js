// get the plugin object from videojs
var Plugin = videojs.getPlugin('plugin');

/**
 * Hash a string (fast & cheap)
 */
Object.defineProperty(String.prototype, 'hashCode', {
  value: function() {
    var hash = 0, i, chr;
    for (i = 0; i < this.length; i++) {
      chr   = this.charCodeAt(i);
      hash  = ((hash << 5) - hash) + chr;
      hash |= 0; // Convert to 32bit integer
    }
    return hash;
  }
});

/**
 * simulate karaoke style subtitles (mozilla's vtt.js seems not to support them)
 */
var KaraokeSubtitles = videojs.extend(Plugin, {

  constructor: function(player, options) {
    Plugin.call(this, player, options);

    // memory for the cues that are already processed
    // contains hashes of cue.startTime, cue.endTime and cue.text
    var processedCues = [];

    player.on('loadeddata', function() {
        textTracks = player.textTracks();
        if (1 in textTracks) {

            // select the first text track if it exists
            var track = textTracks[1];

            // when a cue is changed
            track.on('cuechange', function (e) {
                var cues = track.cues;

                // if there is a cue, loop over all cues
                if (0 in cues) {
                    for (k = 0; k < cues.length; k++) {

                        var cue = cues[k];
                        var hash = JSON.stringify({ s: cue.startTime, e: cue.endTime, t: cue.text }).hashCode();

                        // if the cue is set and not yet processed
                        if (typeof cue !== 'undefined' && processedCues.indexOf(hash) === -1) {
                            processedCues.push(hash);

                            // extract times, and split the full cue into subcues
                            var times = cue.text.match(/([0-9]+\:[0-9]+\:[0-9]+\.[0-9]+)/g);
                            var texts = cue.text.split(/\<[0-9]+\:[0-9]+\:[0-9]+\.[0-9]+\>/);
                            var cueStartTimes = [ cue.startTime ];
                            var cueTexts = [ "<c.now>" + texts[0] + "</c><c.future>" + texts.slice(1).join("") + "</c>" ];
                            var cueEndTimes = [];

                            if (times !== null) {

                                // calc the start times in mili-seconds when the cue should appear
                                for (i = 0; i < times.length; i++) {

                                    a = times[i].match(/([0-9]+)\:([0-9]+)\:([0-9]+)\.([0-9]+)/);
                                    tot_ms = parseInt(a[4])/1000 + parseInt(a[3]) + parseInt(a[2])*60 + parseInt(a[1])*60*60;
                                    cueStartTimes.push(tot_ms);

                                    // determine past, present and future parts of the current cue
                                    var past = texts.slice(0, i+1).join("");
                                    var now = texts[i+1];
                                    var future = texts.slice(i+2).join("");
                                    cueTexts.push("<c.past>" + past + "</c><c.now>" + now + "</c><c.future>" + future + "</c>");
                                }

                                // calc the end times in mili-seconds when the cue should disappear
                                for (i = 0; i < cueStartTimes.length; i++) {
                                    if (i+1 in cueStartTimes) {
                                        cueEndTimes.push(cueStartTimes[i+1]);
                                        var end = cueStartTimes[i+1];
                                    } else {
                                        cueEndTimes.push(cue.endTime);
                                        var end = cue.endTime;
                                    }
                                }

                                // remove the original cue ...
                                track.removeCue(cue);

                                // ... and registrate the new cue(s)
                                for (i = 0; i < cueStartTimes.length; i++) {
                                    var newCue = new window.VTTCue(cueStartTimes[i], cueEndTimes[i], cueTexts[i]);
                                    processedCues.push(JSON.stringify({ s: newCue.startTime, e: newCue.endTime, t: newCue.text }).hashCode());
                                    track.addCue(newCue);
                                }
                            }
                        }
                    }
                }
            });
            //track.mode = 'hidden'; /* This causes subtitles to not be shown on default */
        }
    });

    /*player.on('loadeddata', function() {
        textTracks = player.textTracks();
        if (1 in textTracks) {
            var track = textTracks[1];
            track.on('cuechange', function (e) {
                var ac = track.activeCues;
                if (0 in ac) {
                    var cue = ac[0];
                    if (typeof cue !== 'undefined') {
                        var time = cue.text.match(/\<[0-9]+\:[0-9]+\:[0-9]+\.[0-9]+\>/);
                        var texts = cue.text.split(time);
                        //console.log(time, texts)
                        if (time !== null) {
                            [tot, h, m, s, ms] = time[0].match(/\<([0-9]+)\:([0-9]+)\:([0-9]+)\.([0-9]+)\>/);
                            int = parseInt(ms)/1000 + parseInt(s) + parseInt(m)*60 + parseInt(h)*60*60;
                            var start = cue.startTime;
                            var end = cue.endTime;
                            track.removeCue(cue);
                            track.addCue(new window.VTTCue(start, int, texts[0]));
                            track.addCue(new window.VTTCue(int, end, texts[0] + texts[1]));
                        } else {
                            console.log(texts[0])
                            //$(".js-keylogger__log").append(texts[0] + "<br>");
                        }
                    }
                }
            });
            track.mode = 'hidden';
        }
    });*/

  }
});

videojs.registerPlugin('karaokeSubtitles', KaraokeSubtitles);
