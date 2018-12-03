let kingdom = [
    {
        properties:{
            name: "RES1",
            id: 1,
            resources: [
                {
                    amount: 4,
                    type: "car",
                    img: "img/icons/worker.svg",
                    rights:[
                        {
                            "name": "eat",
                            "amount": 2,
                            "color": "blue"
                        },
                        {
                            "name": "move",
                            "amount": 4,
                            "color": "green"
                        }
                    ]
                },
                {
                    amount: 3,
                    type: "man",
                    img: "img/icons/car.svg",
                    rights:[
                        {
                            "name": "trust",
                            "amount": 3,
                            "color":"orange"
                        },
                        {
                            "name": "pray",
                            "amount": 1,
                            "color":"red"
                        },
                        {
                            "name":"obey",
                            "amount":2,
                            "color":"grey"
                        }
                    ]
                }
            ]
        },
        geometry: {
            type: "polygon",
            coordinates: []
        }
    },
    {
    },
    {
    }
];

let mainData = [{
    "name": "worker",
    "count": "24",
    "icon_path": "img/icons/worker.svg"
},{
    "name": "car",
    "count": "24",
    "icon_path": "img/icons/car.svg"
}];

let ResData = {
    "RESId" : "2440020600",
    "count" : 12,
    "walk" : true,
    "name" : "Электромонтеры",
    "icon_path" : "icon/3.png",
    "staff" : [
        {
            "name" : "Распредсеть",
            "feature" : "В",
            "default" : {
                "attribute" : "Без специальных разрешений",
                "color" : "#FFFFFF",
                "color_icon" : "000000"
            },
            "attributes" : [
                {
                    "count" : 2,
                    "attribute" : "Оперативные права",
                    "color" : "#00FF00",
                    "color_icon" : "#FFFFFF"
                },
                {
                    "count" : 6,
                    "attribute" : "Производитель работ",
                    "color" : "#FFFF00",
                    "color_icon" : "#FFFFFF"
                }
            ]
        }
    ]
};