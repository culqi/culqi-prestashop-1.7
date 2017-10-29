# Culqi JS v2 - Prestashop 1.7

### Pasos para la integración del Módulo de Culqi

#### 1. Registrarse en [Culqi](https://www.culqi.com/)

Así podrás tener acceso al [ambiente de pruebas de Culqi](<https://integ-panel.culqi.com/)
donde encontrarás [tus llaves](https://integ-panel.culqi.com/#/desarrollo/llaves/)

`Llave publica: pk_test_xxxxxxxxxxxxxx`

`Llave privada: sk_test_xxxxxxxxxxxxxx`

#### 2. Descargar  el [Módulo de Culqi 3.0.4](https://github.com/culqi/culqi-prestashop-1.7/releases/tag/v3.0.4/)

![Imgur](https://i.imgur.com/sWLEajr.png)

#### 3. Subir el Módulo de Culqi en tu administrador de Prestashop 1.7

##### 3.1
![Imgur](https://i.imgur.com/zcE8bUp.png)

##### 3.2
![Imgur](https://i.imgur.com/S0nIcXt.png)

#### 4. Configurar el Módulo de Culqi en tu administrador de Prestashop 1.7

##### 4.1
![Imgur](https://i.imgur.com/vdwhGv3.png)

##### 4.2
![Imgur](https://i.imgur.com/dTwx3Pw.png)

Aquí van las llaves que mencionamos en el paso 1 (Registrarse en Culqi).

### Finalmente debes tener a Culqi como pasarela de pago de esta manera:

![Imgur](https://i.imgur.com/Zu66mdM.png)

Debes usar las [tarjetas de prueba](https://culqi.com/docs/#/desarrollo/tarjetas/) que Culqi te ofrece para hacer las pruebas necesarias.

### Pase a producción:

#### 1. Cumplir con los [requisitos técnicos](https://culqi.com/docs/#/desarrollo/produccion/)

#### 2. Activar comercio desde tu panel de integración de Culqi

![Imgur](https://i.imgur.com/wVOz6cc.png)

Si tienes más dudas con respecto al proceso de "Activación de comercio" escríbenos a unete@culqi.com

Cuando te envíen los accesos a tu panel de producción de Culqi debes reemplazar
tus llaves de pruebas por tus llaves de producción como en el paso 4.2 

* Llave pública: `pk_live_xxxxxxxxxxxxxx`
* Llave privada: `sk_live_xxxxxxxxxxxxxx`

En el ambiente de producción podrás comenzar a usar tarjetas reales.

### Si tienes dudas de Integración escríbenos a integrate@culqi.com

### Si tienes dudas comerciales escríbenos a unete@culqi.com
