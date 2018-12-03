class Popup_worker extends Popup_extended {
    constructor() {
        super();
    }

    set popup_text(item) {
        var times = "";
        if (item.properties.time) {
            times = item.properties.time;
            times = times.toString();
            times = times.replace('T', " ");
            times = times.replace('Z', "");
        }
        let text = 'Наименование: <b><span>' + item.properties.name + '</b><br>IMEI: ' + item.properties.deviceId +
                '<br>Время: ' + times;

        text += this.get_info(item);
        text += this.get_hrefs(item);
        text += '<br><button class="btn btn-success btn-sm block_top" type="button" data-toggle="collapse" data-target="#message" aria-expanded="false" aria-controls="collapseExample">' +
                'Дополнительно</button><hr>' +
                '<div class="message collapse block_top" id="message">Сообщение: <br><textarea class="form-control" name="message" id="popup_message"></textarea>' +
                '<br><span>Статус: </span><span name="popup_lable" class="popup_lable"><lable name="info" class="btn-sm"></lable></span><br>' +
                '<br><a class="btn btn-info btn-sm btn-kml block_top col" href="/api/getobjs?type=Track&deviceId='+btoa(item.properties.deviceId)+'&format=KML&filename='+item.properties.deviceId+'" role="button">сохранить KML</a>'+
                '<button class="btn btn-success btn-sm block_top col" name="send" id="' + item.properties.deviceId +
                '">Отправить</button>';
        text += '<br><button class="btn btn-primary btn-sm track block_top col" id="' + item.properties.deviceId + '">Показать трек</button></span></div>';
        this._popup_text = text;
    }
    
    get popup_text() {
        return this._popup_text;
    }
    
    bind_popup(layer, cheack_near) {
        layer.bindPopup(this.popup_text, {
            keepInView: true,
            cheack_near: cheack_near
        });
    }
}