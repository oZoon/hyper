<?php
include_once 'core/config.php';

init(); // инициализация

doParseRequest(); // парсинг входящих параметров

doSign();// регистрирация новых пользователей (если разрешено)

doLogin(); // получение code

doCode(); // получение token

doList(); // список изображений (если разрешено)

doGet(); // получение информации об изображении

doImage(); // получение самого изображения