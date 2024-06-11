<?php

use Bitrix\Main\Component\ParameterSigner;
use Nette\Utils\Html;

$params = $arResult['PARAMS'];
$row = $arResult['ROW'];

$APPLICATION->SetPageProperty("keywords", "интернет-магазин, стоматологические материалы, заказать, купить");
$APPLICATION->SetPageProperty("title", $params["titleTag"]);
$APPLICATION->SetPageProperty("viewed_show", "Y");
$APPLICATION->SetTitle($params["title"]);
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!--noindex-->
<div class="maxwidth-theme">
    <div id="permalink" class="form inline">
        <div class="form_desc">
            <?= htmlspecialchars_decode($params['html']) ?>
        </div>
        <form name="form-contact" method="POST">
            <div class="form_body">
                <div class="row">
                    <div class="col-md-5">
                        <input type="hidden" name="hash" value="<?= $row['UF_HASH'] ?>">
                        <input type="hidden" name="responsible" value="<?= $row['UF_RESPONSIBLE'] ?>">
                        <div class="form-control">
                            <label><span>Дата&nbsp;<span class="star">*</span></span></label>
                            <input type="date" required class="inputtext" name="date" value="<?= $params['date'] ?>"
                                   data-sid="DATE" aria-required="true" disabled>
                        </div>
                        <div class="form-control">
                            <label><span>Мероприятие <span class="star">*</span></span></label>
                            <input name="event" required placeholder="Название мероприятия" class="inputtext"
                                   type="text" value="<?= $params['event'] ?>" data-sid="EVENT" aria-required="true"
                                   disabled>
                        </div>
                        <div class="form-control">
                            <label><span>Город <span class="star">*</span></span></label>
                            <input name="city" required placeholder="Город мероприятия" class="inputtext"
                                   type="text" value="<?= $params['city'] ?>" data-sid="CITY" aria-required="true"
                                   disabled>
                        </div>
                        <div class="form-control">
                            <label><span>Лектор <span class="star">*</span></span></label>
                            <input name="teacher" required placeholder="" class="inputtext" type="text"
                                   value="<?= $params['teacher'] ?>" data-sid="TEACHER" aria-required="true"
                                   disabled>
                        </div>
                        <div class="form-control">
                            <label><span>Имя <span class="star">*</span></span></label>
                            <input name="first_name" required placeholder="" class="inputtext" type="text" value=""
                                   data-sid="FIRST_NAME" aria-required="true">
                        </div>
                        <div class="form-control">
                            <label><span>Фамилия <span class="star">*</span></span></label>
                            <input name="second_name" required placeholder="" class="inputtext" type="text" value=""
                                   data-sid="SECOND_NAME" aria-required="true">
                        </div>
                        <div class="form-control">
                            <label><span>Телефон <span class="star">*</span></span></label>
                            <input name="phone" required placeholder="" class="inputtext phone" type="text" value=""
                                   data-sid="PHONE" aria-required="true">
                        </div>
                        <div class="form-control">
                            <label><span>E-mail <span class="star">*</span></span></label>
                            <input name="email" required placeholder="" class="inputtext" type="email" value=""
                                   data-sid="EMAIL" aria-required="true">
                        </div>
                        <div class="form-control">
                            <label><span>Специализация <span class="star">*</span></span></label>
                            <select id="list" name="spec[]" required class="usual" multiple="multiple">
                                <?php
                                foreach ($arResult['SPEC_ENUMS'] as $spec) {
                                    ?>
                                    <option value="<?= $spec['ID'] ?>"><?= $spec['VALUE'] ?></option>
                                    <?php
                                } ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label><span>Наименование Клиники</span></label>
                            <input name="clinic_name" placeholder="" class="inputtext" type="text" value=""
                                   data-sid="CLINIC_NAME">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form_footer">
                <div class="error-form"></div>
                <input type="submit" class="btn btn-default" value="Отправить">
            </div>
        </form>
    </div>
</div>
<!--/noindex-->