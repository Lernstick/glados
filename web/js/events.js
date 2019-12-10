
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
    console.log('debug | ', {
        id: e.id,
        type: 'none',
        data: 'Connection was opened.',
        handler: EventStream.prototype.openHandler
    });
}

EventStream.prototype.errorHandler = function(e) {
    console.log('debug | ', {
        id: e.id,
        type: 'none',
        data: 'Error - connection was lost.',
        handler: EventStream.prototype.errorHandler
    });
}

EventStream.prototype.messageHandler = function(e) {
    console.log('debug | ', {
        id: e.id,
        type: 'none',
        data: e.data,
        handler: EventStream.prototype.messageHandler
    });
}

function debugHandler(e, me){
    if (YII_DEBUG) {
        var data = JSON.parse(e.data);
        console.log('debug | ', {
            id: e.id,
            type: e.type,
            data: e.data,
            handler: me
        });
    }
}
