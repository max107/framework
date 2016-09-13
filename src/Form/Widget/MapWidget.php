<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 22:53
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\Widget;
use Mindy\Helper\JavaScript;

class MapWidget extends Widget
{
    /**
     * @var string
     */
    public $latField = 'lat';
    /**
     * @var string
     */
    public $lngField = 'lng';
    /**
     * @var int
     */
    public $zoom = 12;
    /**
     * @var array
     */
    public $center = [55.76, 37.64];

    /**
     * @param FieldInterface $field
     * @return string
     */
    public function render(FieldInterface $field) : string
    {
        $center = JavaScript::encode($this->center);

        $form = $field->getForm();
        $latField = $form->getField($this->latField);
        $lngField = $form->getField($this->latField);

        if ($latField->getValue() && $lngField->getValue()) {
            $center = JavaScript::encode([$latField->getValue(), $lngField->getValue()]);
        }

        $map = "<div id='" . $field->getHtmlId() . "map'></div>
        <script src='//api-maps.yandex.ru/2.1/?lang=ru_RU' type='text/javascript'></script>
        <script type='text/javascript'>
            function yandexMapInit() {
                var mapCollection = new ymaps.GeoObjectCollection({}, {
                   preset: 'twirl#redIcon'
                });

                var yandexMap = new ymaps.Map('" . $field->getHtmlId() . "map', {
                    center: " . $center . ",
                    zoom: " . $this->zoom . ",
                    controls: ['zoomControl', 'searchControl']
                });

                var center = yandexMap.getCenter();

                mapCollection.add(new ymaps.GeoObject({
                    geometry: {
                        type: 'Point',
                        coordinates: center
                    }
                }));
                yandexMap.geoObjects.add(mapCollection);

                $('#" . $latField->getHtmlId() . "').val(center[0].toPrecision(6));
                $('#" . $lngField->getHtmlId() . "').val(center[1].toPrecision(6));

                yandexMap.events.add('click', function (e) {
                    var coords = e.get('coords');

                    $('#" . $latField->getHtmlId() . "').val(coords[0].toPrecision(6));
                    $('#" . $lngField->getHtmlId() . "').val(coords[1].toPrecision(6));

                    mapCollection.removeAll();
                    mapCollection.add(new ymaps.GeoObject({
                        geometry: {
                            type: 'Point',
                            coordinates: coords
                        }
                    }));
                    yandexMap.geoObjects.add(mapCollection);
                });
            }

            ymaps.ready(yandexMapInit);
        </script>
        <style>
            #" . $field->getHtmlId() . "map {
                width: 100%;
                height: 350px;
                margin: 20px 0;
            }
        </style>";

        return $map . $field->render();
    }
}