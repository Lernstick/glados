// get the plugin object from videojs
var Plugin = videojs.getPlugin('plugin');

/**
 * simulate karaoke style subtitles (mozilla's vtt.js seems not to support them)
 */
var KaraokeSubtitles = videojs.extend(Plugin, {

  constructor: function(player, options) {
    Plugin.call(this, player, options);

    player.on('loadeddata', function() {
        textTracks = player.textTracks();
        if (1 in textTracks) {

            // select the first text track if it exists
            var track = textTracks[1];

            // when a cue is changed
            track.on('cuechange', function (e) {
                var activeCues = track.activeCues;

                // if there is an active cue
                if (0 in activeCues) {
                    var cue = activeCues[0];

                    // if the cue is set
                    if (typeof cue !== 'undefined') {

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
                                track.addCue(new window.VTTCue(cueStartTimes[i], cueEndTimes[i], cueTexts[i]));
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
