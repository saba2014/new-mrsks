class Popup_ztp extends Popup_extended {
    constructor() {
        super();
    }

    set popup_text(item) {
        let doknr = '\"'+item.properties.doknr+'\"';
        let kapzatr = '<br> Не требует кап. затрат';
        if (item.properties.kapzatr)
            if (item.properties.kapzatr!=="")
                kapzatr = '<br> Требует кап. затрат';
        let text = '№ заявки: <b><span>' + item.properties.doknr;
        text += "<button сlass='copy-text' onclick='Popup_extended.copyTechPlaceToClipboard("+doknr+")' data-clipboard-text='" + item.properties.doknr +
            "'>Копировать</button></button></span></b>";
        text += '<br>Мощность: ' + item.properties.power +
            '<br>Дата: ' + item.properties.date +
            '<br>Статус: ' + item.properties.status +
            kapzatr +
            isPropertieExsist(item.properties, "data_okon", "Дата окончания: ")+
            isPropertieExsist(item.properties, "voltage_val", "Напряжение: ")+
            isPropertieExsist(item.properties, "category", "Категория надежности: ")+
            isPropertieExsist(item.properties, "main_tplnr_ps", "Основной центр питания: ")+
            isPropertieExsist(item.properties, "rezerv_tplnr_point", "Резервная точка присоединения: ")+
            isPropertieExsist(item.properties, "rezerv_tplnr_ps", "Резервный источник питания: ")+
            isPropertieExsist(item.properties, "ztu_dokrn", "Тех. поле: ")+
            isPropertieExsist(item.properties, "main_tplnr_point", "Основная точка присоединения: ");
        this._popup_text = text;
    }
    
    get popup_text() {
        return this._popup_text;
    }
}