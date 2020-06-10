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
                var ac = track.activeCues;

                // if there is an active cue
                if (0 in ac) {
                    var cue = ac[0];

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
            track.mode = 'hidden';
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

// TODO: videooverlay class file

var Button = videojs.getComponent('Button');
var switchButtom = videojs.extend(Button, {
    constructor: function(player, options) {
        /* initialize your button */
        Button.call(this, player, options);
        this.addClass('vjs-icon-picture-in-picture-enter');
        this.controlText('Switch overlay and main player');
    },

    handleClick: function() {

        var mp = this.options_.mainPlayer;
        var sp = this.options_.sidePlayer;

        var parent = $(mp.el()).parent();
        $(mp.el()).appendTo($("body"));
        $(sp.el()).appendTo($("body"));

        $(sp.el()).appendTo($(parent));
        $(mp.el()).appendTo($(sp.el()));

        // flip all the classes and width, height
        var mp_classes = $(mp.el()).attr('class');
        var sp_classes = $(sp.el()).attr('class');
        var sp_width  = $(sp.el()).width();
        var sp_height = $(sp.el()).height();
        $(sp.el()).attr("class", mp_classes);
        $(mp.el()).attr("class", sp_classes);
        $(sp.el()).width('initial');
        $(sp.el()).height('initial');
        $(mp.el()).width(sp_width);
        $(mp.el()).height(sp_height);

        mp.controlBar.hide();
        sp.controlBar.show();
    }
});

videojs.registerComponent('switchButtom', switchButtom);

var VideoOverlay = videojs.extend(Plugin, {

  constructor: function(player, options) {
    Plugin.call(this, player, options);

    player.on('ready', function() {

        var player2 = videojs(options.id, options);
        player.el().appendChild(player2.el())
        $(player2.el()).addClass('vjs-dragable vjs-overlay');

        var btn1 = player.controlBar.addChild('switchButtom', {
            mainPlayer: player,
            sidePlayer: player2
        });

        var btn2 = player2.controlBar.addChild('switchButtom', {
            mainPlayer: player2,
            sidePlayer: player
        });
    });

  }
});

videojs.registerPlugin('videoOverlay', VideoOverlay);