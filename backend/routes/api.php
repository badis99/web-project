<?php

declare(strict_types=1);

$router->get('/api/health', fn () => $healthController->check());

$router->get('/api/workshops', fn () => $workshopController->index());
$router->get('/api/workshops/{id}', fn (Request $request, string $id) => $workshopController->show($request, $id));
$router->post('/api/workshops', fn (Request $request) => $workshopController->store($request));
$router->patch('/api/workshops/{id}', fn (Request $request, string $id) => $workshopController->update($request, $id));
$router->delete('/api/workshops/{id}', fn (Request $request, string $id) => $workshopController->destroy($request, $id));

$router->get('/api/workshop-registrations', fn () => $registrationController->index());
$router->post('/api/workshop-registrations', fn (Request $request) => $registrationController->store($request));

// Video upload: POST /api/upload/video  (multipart/form-data, field: "video")
// Returns { "url": "https://...supabase.co/storage/v1/object/public/workshop-videos/..." }
$router->post('/api/upload/video', fn (Request $request) => $videoUploadController->upload($request));
