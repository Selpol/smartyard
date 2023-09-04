<?php
/**
 * @api {post} /address/registerQR зарегистрировать QR код
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String} - показать alert c текстом
 *
 * @apiParam {String} QR QR код
 */

/** @var array $postdata */

$jwt = jwt();
$audJti = $jwt['scopes'][1];

$code = trim(@$postdata['QR']);

if (!$code)
    response(400, false, 'Неверный формат QR кода', 'Неверный формат QR кода');

//полагаем, что хэш квартиры является суффиксом параметра QR
$hash = '';

for ($i = strlen($code) - 1; $i >= 0; --$i) {
    if (!in_array($code[$i], ['/', '=', "_"]))
        $hash = $code[$i] . $hash;
    else
        break;
}

if ($hash == '')
    response(200, "QR-код не является кодом для доступа к квартире");

$households = backend("households");
$flat = $households->getFlats("code", ["code" => $hash])[0];

if (!$flat)
    response(200, "QR-код не является кодом для доступа к квартире");

$flat_id = (int)$flat["flatId"];

$subscribers = $households->getSubscribers('aud_jti', $audJti);

if (!$subscribers || count($subscribers) === 0) {
    $mobile = trim(@$postdata['mobile']);
    $name = trim(@$postdata['name']);
    $patronymic = trim(@$postdata['patronymic']);

    if (strlen($mobile) !== 11)
        response(400, false, 'Неверный формат номера телефона', 'Неверный формат номера телефона');

    if (!$name) response(400);
    if (!$patronymic) response(400);

    if ($households->addSubscriber($mobile, $name, $patronymic)) {
        $subscribers = $households->getSubscribers('mobile', $mobile);

        if (count($subscribers) > 0)
            $households->modifySubscriber($subscribers[0]['subscriberId'], ['audJti' => $audJti]);
    } else response(422, 'Не удалось зарегестрироваться');
}

if ($subscribers && count($subscribers) > 0) {
    $subscriber = $subscribers[0];

    foreach ($subscriber['flats'] as $item)
        if ((int)$item['flatId'] == $flat_id)
            response(200, "У вас уже есть доступ к данной квартире");

    if ($households->addSubscriber($subscriber["mobile"], null, null, $flat_id))
        response(200, "Ваш запрос принят и будет обработан в течение одной минуты, пожалуйста подождите");
    else response(422);
} else response(404);
