class Personal extends Popup {
    constructor() {
        super();
    }

    popup_text(item) {
        let name = "Отусутствует";
        let self = item.data;
        let number = item.number;
        if (self.name !== undefined) {
            name = self.name;
        }
        let text = '<div><b>Наименование:</b> ' + name +'</div>';
        for(let i = 0; i < self.rights.length; i++) {
            text += '<div>' + self.rights[i].name +'</div>';
        }
        text += '<div><input type="button" class="btn btn-sm btn-danger" onclick="deleteMe(' + number + ')" value="Удалить"></div>';
        this._popup_text = text;
        return text;
    }

}