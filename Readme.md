Integración de WooCommerce con Positive Software - Documentación del Proyecto

 * Descripción del Proyecto:
El objetivo de este proyecto es integrar una tienda WooCommerce con el servicio externo de punto de venta (POS) llamado Positive Software a traves de un servicio llamado Positive Anywhere. La integración permitirá mantener sincronizada la base de datos de WooCommerce y la base de datos de que posee el cliente en Positive Software, de modo que cualquier cambio realizado en el stock de Positive Software se refleje automáticamente en la base de datos de woocommerce y viceversa.

 · Dominio del cliente: https://www.officeexpert.com.
 · Dominio de la documentacion de Positive Software: https://https://www.positive.software
 · Servidor de prueba: https://www.oe.bohiques.com


 * Pasos para la Integración:

    * Paso 1: Activar Positive Anywhere: Se debe iniciar el programa Positive Anywhere, el cual es el responsable de levantar el servidor y la base de datos que queremos integrar

    * Paso 2: Consumir la API de Positive: Una vez iniciado Positive Anywhere, las peticiones se realizaran hacia la url: 'https://www.https://oepc.positiveanywhere.com/', donde deberas realizar la respectiva autenticacion para establecer la conexion. -- Para mas informacion acerca de los endpoints de la API visita 'https://www.positive.software/api-integration'
