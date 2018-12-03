class Popup_track extends Popup_extended {
    constructor() {
        super();
    }

    set popup_text(item) {
        var times = item.properties.time;
        times = times.replace('T'," ");
        times = times.replace('Z',"");
        let text = 'Наименование: <b><span>' + item.properties.name + '</b><br>IMEI: ' + item.properties.deviceId +
                '<br>Время: ' + times;

        text += this.get_info(item);
        text += this.get_hrefs(item);
        var name = item.properties.name;
        var id = item.properties.deviceId;
       text += '<br><a class="btn btn-success btn-sm btn-kml block_top" href="/api/getobjs?type=Track&deviceId='+btoa(id)+'&format=KML&filename=test" role="button">KML</a>';
       text += '<a class="btn btn-danger btn-sm btn-kml block_top ml-2" id='+item.properties.deviceId+' onclick="DeleteLayersInWork()" role="button">Скрыть трэк</a>';
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