class popupRISE extends Popup {
    constructor() {
        super();
    }

    set popup_text(item) {
        let name = "Отусутствует";
        if (item.properties.name !== undefined) {
            name = item.properties.name;
        }
        let text = '<b>Наименование:</b> ' + name;
        text += this.get_info(item);
        if(item.properties.buildingPercent!==undefined){
            text+="<br><b>Процент строительства</b>: "+ item.properties.buildingPercent;
        }
        text += this.get_hrefs(item);
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }
}