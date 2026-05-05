<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

// Create SQLite Database and Table if it doesn't exist
$db = new PDO('sqlite:movies.db');
$db->exec("CREATE TABLE IF NOT EXISTS movies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    author TEXT,
    rating INTEGER,
    image_path TEXT,
    notes TEXT
)");

// CORS Middleware (Allows React to talk to PHP)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

// Route: Save Movie
$app->post('/api/movies', function (Request $request, Response $response) use ($db) {
    $directory = __DIR__ . '/uploads';
    if (!is_dir($directory)) mkdir($directory, 0777, true);

    $uploadedFiles = $request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['image'];
    
    $filename = bin2hex(random_bytes(8)) . "." . pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    $data = $request->getParsedBody();
    
    $stmt = $db->prepare("INSERT INTO movies (title, author, rating, image_path, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['title'], $data['author'], $data['rating'], 'uploads/' . $filename, $data['notes']]);

    $response->getBody()->write(json_encode(['status' => 'success']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Route: Get Movies
$app->get('/api/movies', function (Request $request, Response $response) use ($db) {
    $stmt = $db->query("SELECT * FROM movies");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($movies));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/api/movies/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];

    // 1. Find the image path to delete the file
    $stmt = $db->prepare("SELECT image_path FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($movie) {
        $fullPath = __DIR__ . '/' . $movie['image_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath); // Delete the actual image file
        }

        // 2. Delete from database
        $stmt = $db->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$id]);
    }

    $response->getBody()->write(json_encode(['status' => 'deleted']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();