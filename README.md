<h1>ogo</h1>

<p>PHP HMVC framework.</p>

Require the "_init.php" file and start to enjoy.

<h2>EXAMPLE:</h2>

```php
require "_init.php";

$app = \ogo\App::create();

$app->add_route(
	"get post put delete", 
	"/[:module]/[:action]", 
	function ($request, $params) use ($app) {
		try {
			$response = $app->call(
				"APP.modules.{$params["module"]}", 
				$params["action"], 
				$request->get_data()
			);
			$response->set_type("json");
			return $response;
		}
		catch (\ogo\Exception $e) {
			$response = new \ogo\Response("error");
			$response->set_type("json");
			$response->set_data($e->get_message());
		}
		return $response;
	}
);

$app->def(function ($request) use ($app) {
	$app->redirect("/welcome/index");
});

// RUN
$response = $app->run();
$response->render();
```
