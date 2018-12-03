class Popup_message extends Popup_extended {
    constructor() {
        super();
        this.onlytext = false;
    }

    set popup_text(item) {
        let text = '';
        text += '<div><h3>сообщение: <h3><br>';
        text += '<p>' + item.text + '</p> <br>';
        text += '<h3>Ссылки: </h3>';
        if (item.hrefs)
            for (var i = 0; i < item.hrefs.length; i++)
                text += '<p> <span class="label label-default">' + item.hrefs[i].name + '</span> :' + item.hrefs[i].href + '</p></div>';
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }


    set tooltip_text(item) {
        let text = "";
        if ((item.properties.message) || (item.properties.deviceId)) {
            var LimName = GetFirstSymbols(20, item.properties.deviceId);
            if(item.properties.worker_name) {
                var LimName = GetFirstSymbols(20, item.properties.worker_name);
            }
            text += 'От: ' + LimName + '</div>';
            var LimText = GetFirstSymbols(20, item.properties.message);
            text += '<div>' + LimText + '<br>';

        }
        else text += "Важное сообщение";
        this._tooltip_text = text;
    }


    get tooltip_text() {
        return this._tooltip_text;
    }

    set tooltip_text_more(item) {
        this._tooltip_text_more = item;
    }

    get tooltip_text_more() {
        return this._tooltip_text_more;
    }


}