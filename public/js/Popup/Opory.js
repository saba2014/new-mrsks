class Popup_opory extends Popup_extended {
    constructor() {
        super();
        this.onlytext = false;
    }

    set popup_text(item) {
        let text = '';
        let json = this.get_json(item, this.Report(item), "ps");
        text += this.get_svg(item);
        text += 'Имя: <b>' + item.properties.NoInLine + '</b>';
        text += this.get_balance(item);
        text += '<br>ТехМесто: <b><span>' + item.properties.tplnr +
            '</span></b><br><button сlass="copy-text" onclick=Popup_extended.copyTechPlaceToClipboard("' + item.properties.tplnr + '") data-clipboard-text="' +
            (item.properties.tplnr === undefined ? 'неизвестно' : item.properties.tplnr) +
            '">Копировать</button>';
        if (!this.onlytext) {
            text += "<input type='button' value='Выбрать' id='addBtn' onclick='N.addToReport(" + json + ")'/>";
            if (item.properties.buildingPercent) {
                text += "<br>Процесс строительства: ";
                text += item.properties.buildingPercent + '%';
            }
        }
        text += this.get_hrefs(item);
        text += '<br><a href="api/getSap&tplnr=' + item.properties.tplnr + '">перейти в SAP</a>';
        text += this.get_hrefs(item);
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }

    Report(item) {
        let report = 'Имя: <b>' + item.properties.NoInLine + '</b>';
        report += this.get_balance(item);
        report += '<br>ТехМесто: <b>' +
            (item.properties.tplnr === undefined ? "неизвестно" : item.properties.tplnr) + '</b>';
        return report;
    }
}