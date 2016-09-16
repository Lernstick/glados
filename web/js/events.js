
function EventStream(url){
    this.url = url;

    if (!!window.EventSource) {

        if(typeof source !== 'object'){
            this.source = new EventSource(this.url);
        }

        this.source.addEventListener('open', this.openHandler, false);
        this.source.addEventListener('error', this.errorHandler, false);
        this.source.addEventListener('message', this.messageHandler, false);

    }
}

EventStream.prototype.openHandler = function(e) {
//    console.log("debug | Connection was opened.");
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
//    console.log("debug | Error - connection was lost.");
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
    var data = JSON.parse(e.data);
//    if(data.debug){
//        console.log('debug | raw event    : ', e);
//        console.log('debug | debug data   : ', data.debug)
//        console.log('debug | event data   : ', data.data);
//        console.log('debug | generated at : ', data.generated_at);
//        console.log('debug | sent at      : ', data.sent_at);
        console.log('debug | ', {
            id: e.id,
            type: e.type,
            data: e.data,
            handler: me
        });
//    }

}
