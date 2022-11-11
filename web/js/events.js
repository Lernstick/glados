
/**
 * Hash a string (fast & cheap)
 */
if (String.prototype.hasOwnProperty('hashCode') === false) {
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
}

function EventStream(url){
    this.url = url;

    if (!!window.EventSource) {

        if(typeof source !== 'object'){
            this.source = new EventSource(this.url);
        }

        if (YII_DEBUG) {
            this.source.addEventListener('open', this.openHandler, false);
            this.source.addEventListener('error', this.errorHandler, false);
            this.source.addEventListener('message', this.messageHandler, false);
        }

    }
}

EventStream.prototype.openHandler = function(e) {
    console.debug('debug | ', {
        id: e.id,
        type: 'none',
        data: 'Connection was opened.',
        handler: EventStream.prototype.openHandler
    });
}

EventStream.prototype.errorHandler = function(e) {
    console.debug('debug | ', {
        id: e.id,
        type: 'none',
        data: 'Error - connection was lost.',
        handler: EventStream.prototype.errorHandler
    });
}

EventStream.prototype.messageHandler = function(e) {
    console.debug('debug | ', {
        id: e.id,
        type: 'none',
        data: e.data,
        handler: EventStream.prototype.messageHandler
    });
}

function debugHandler(e, me){
    if (YII_DEBUG) {
        var data = JSON.parse(e.data);
        console.debug('debug | ', {
            id: e.id,
            type: e.type,
            data: e.data,
            handler: me
        });
    }
}

/**
 * The following functions mimic the behavior of their YII counterparts.
 * @see https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter
 */

/**
 * Raw formatter, does not change anything
 * @see https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter#asRaw()-detail
 */
function formatter_asRaw(value) { return value; }

/**
 * @see equivalent to PHP method [[customFormatter->asLinks()]]
 */
function formatter_asLinks(value, options = {'remove': false}) {
    return value.replace(/\{url\:([^\:]+)\:([^\:]+)\:([^\:]+)\:([^\}]*)\}/g, function (match, name, controller, action, plist, offset, input_string) {
        if (options.remove) {
            return name;
        }

        var p = plist.split(',');
        var params = {};
        p.forEach(function (param) {
            var parr = param.split('=', 2);
            params[parr[0]] = parr[1];
        });
        params[0] = controller+'/'+action;

        var link = $("<a>");
        link.attr("href", Url_to(params));
        link.attr("data-pjax", 0);
        link.text(name);
        return link.prop('outerHTML');;
    });
}