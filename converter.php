<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['images']) && count($_FILES['images']['error']) > 0) {
        $errors = [];
        $uploadedFiles = [];
        $convertedFiles = [];

        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            $fileName = basename($_FILES['images']['name'][$index]);
            $fileName = str_replace(' ', '_', $fileName);
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $destination = 'uploads/' . $fileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $uploadedFiles[] = $destination;

                    // Create the destination file
                    $convertedFileName = "image_" . basename($fileName) . ".sql";
                    $convertedFilePath = 'uploads/' . $convertedFileName;

                    if (fopen($convertedFilePath, "w")) {
                        $variable = isset($_POST['variable']) ? escapeshellarg($_POST['variable']) : 'cImg';

                        // Comando do script shell para converter a imagem
                        $command = escapeshellcmd("./fileToBase64ClobOracle.sh $destination $variable $convertedFilePath");
                        $output = shell_exec($command);

                        // Verifica se o comando shell foi executado com sucesso
                        if (file_exists($convertedFilePath)) {
                            $convertedFiles[] = $convertedFilePath;
                        } else {
                            $errors[] = "Erro ao converter o ficheiro $fileName. Comando: $command. Saída: $output";
                        }
                    } else {
                        $errors[] = "Não foi possível criar o ficheiro $convertedFileName.";
                    }
                } else {
                    $errors[] = "Erro ao mover o ficheiro $fileName para a pasta de uploads.";
                }
            } else {
                $uploadError = $_FILES['images']['error'][$index];
                switch ($uploadError) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errors[] = "O ficheiro $fileName excede o tamanho máximo permitido.";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errors[] = "O ficheiro $fileName excede o tamanho máximo permitido pelo formulário.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errors[] = "O upload do ficheiro $fileName foi apenas parcialmente concluído.";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errors[] = "Nenhum ficheiro foi enviado.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errors[] = "Pasta temporária ausente.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errors[] = "Falha ao escrever o ficheiro $fileName no disco.";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errors[] = "Upload do ficheiro $fileName interrompido por uma extensão.";
                        break;
                    default:
                        $errors[] = "Erro desconhecido no upload do ficheiro $fileName. Código de erro: $uploadError";
                        break;
                }
            }
        }

        if (empty($errors)) {
            if (count($convertedFiles) === 1) {
                // Apenas um ficheiro convertido, baixa diretamente
                $convertedFilePath = $convertedFiles[0];
                header('Content-Disposition: attachment; filename="' . basename($convertedFilePath) . '"');
                header('Content-Type: application/sql');
                readfile($convertedFilePath);
                unlink($convertedFilePath);
                unlink($uploadedFiles[0]);
            } else {
                // Múltiplos ficheiros convertidos, criar ZIP
                $zip = new ZipArchive();
                $zipFileName = 'converted_files.zip';
                $zipFilePath = 'convertions/' . $zipFileName;

                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                    exit("Não foi possível abrir o ficheiro ZIP.\n");
                }

                foreach ($convertedFiles as $convertedFile) {
                    $zip->addFile($convertedFile, basename($convertedFile));
                }

                $zip->close();

                // Definindo o cabeçalho para download do ficheiro ZIP
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                header('Content-Length: ' . filesize($zipFilePath));

                readfile($zipFilePath);

                // Remover ficheiros temporários
                foreach ($convertedFiles as $convertedFile) {
                    unlink($convertedFile);
                }
                foreach ($uploadedFiles as $uploadedFile) {
                    unlink($uploadedFile);
                }
                unlink($zipFilePath);
            }
        } else {
            foreach ($uploadedFiles as $uploadedFile) {
                unlink($uploadedFile);
            }
            http_response_code(400);
            echo 'Erros encontrados: ' . implode(' ', $errors);
        }
    } else {
        http_response_code(400);
        echo 'Nenhum ficheiro foi enviado ou ocorreu um erro no upload.';
    }
} else {
    http_response_code(405);
    echo 'Método não permitido.';
}


return; // Versão não testada
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['images']) && count($_FILES['images']['error']) > 0) {
        $errors = [];
        $convertedFiles = [];

        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['images']['name'][$index]);
                $destination = 'uploads/' . $fileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    // Create the destination file
                    $convertedFileName = "$fileName.sql";
                    $convertedFilePath = 'uploads/' . $convertedFileName;

                    if (fopen($convertedFilePath, "w")) {
                        // Comando do script shell para converter a imagem
                        $command = escapeshellcmd("./fileToBase64ClobOracle.sh $destination IBase64 $convertedFilePath");
                        $output = shell_exec($command);

                        // Verifica se o comando shell foi executado com sucesso
                        if (file_exists($convertedFilePath)) {
                            $convertedFiles[] = $convertedFilePath;
                        } else {
                            $errors[] = "Erro ao converter o ficheiro $fileName. Comando: $command. Saída: $output";
                        }
                    } else {
                        $errors[] = "Não foi possível criar o ficheiro $convertedFileName.";
                    }
                } else {
                    $errors[] = "Erro ao mover o ficheiro $fileName para a pasta de uploads.";
                }
            } else {
                $errors[] = "Erro no upload do ficheiro $fileName. Código do erro: " . $_FILES['images']['error'][$index];
            }
        }

        if (empty($errors)) {
            if (count($convertedFiles) === 1) {
                // Apenas um ficheiro convertido, baixa diretamente
                $convertedFilePath = $convertedFiles[0];
                header('Content-Disposition: attachment; filename="' . basename($convertedFilePath) . '"');
                header('Content-Type: application/sql');
                readfile($convertedFilePath);
                unlink($convertedFilePath);
            } else {
                // Múltiplos ficheiros convertidos, criar ZIP
                $zip = new ZipArchive();
                $zipFileName = 'converted_files.zip';
                $zipFilePath = 'convertidos/' . $zipFileName;

                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                    exit("Não foi possível abrir o ficheiro ZIP.\n");
                }

                foreach ($convertedFiles as $convertedFile) {
                    $zip->addFile($convertedFile, basename($convertedFile));
                }

                $zip->close();

                // Definindo o cabeçalho para download do ficheiro ZIP
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                header('Content-Length: ' . filesize($zipFilePath));

                readfile($zipFilePath);

                // Remover ficheiros temporários
                foreach ($convertedFiles as $convertedFile) {
                    unlink($convertedFile);
                }
                unlink($zipFilePath);
            }
        } else {
            http_response_code(400);
            echo 'Erros encontrados: ' . implode(' ', $errors);
        }
    } else {
        http_response_code(400);
        echo 'Nenhum ficheiro foi enviado ou ocorreu um erro no upload.';
    }
} else {
    http_response_code(405);
    echo 'Método não permitido.';
}

return; // Excelente versão - v3
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['images']) && count($_FILES['images']['error']) > 0) {
        $errors = [];
        $convertedFiles = [];

        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['images']['name'][$index]);
                $destination = 'uploads/' . $fileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    // Create the destination file
                    $convertedFileName = "$fileName.sql";
                    $convertedFilePath = 'uploads/' . $convertedFileName;

                    if (fopen($convertedFilePath, "w")) {
                        // Comando do script shell para converter a imagem
                        $command = escapeshellcmd("./fileToBase64ClobOracle.sh $destination IBase64 $convertedFilePath");
                        $output = shell_exec($command);

                        // Verifica se o comando shell foi executado com sucesso
                        if (file_exists($convertedFilePath)) {
                            $convertedFiles[] = $convertedFilePath;
                        } else {
                            $errors[] = "Erro ao converter o ficheiro $fileName.";
                        }
                    } else {
                        $errors[] = "Não foi possível criar o ficheiro $convertedFileName.";
                    }
                } else {
                    $errors[] = "Erro ao mover o ficheiro $fileName para a pasta de uploads.";
                }
            } else {
                $errors[] = "Erro no upload do ficheiro $fileName.";
            }
        }

        if (empty($errors)) {
            if (count($convertedFiles) === 1) {
                // Apenas um ficheiro convertido, baixa diretamente
                $convertedFilePath = $convertedFiles[0];
                header('Content-Disposition: attachment; filename="' . basename($convertedFilePath) . '"');
                header('Content-Type: application/sql');
                readfile($convertedFilePath);
                unlink($convertedFilePath);
            } else {
                // Múltiplos ficheiros convertidos, criar ZIP
                $zip = new ZipArchive();
                $zipFileName = 'converted_files.zip';
                $zipFilePath = 'convertidos/' . $zipFileName;

                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                    exit("Não foi possível abrir o ficheiro ZIP.\n");
                }

                foreach ($convertedFiles as $convertedFile) {
                    $zip->addFile($convertedFile, basename($convertedFile));
                }

                $zip->close();

                // Definindo o cabeçalho para download do ficheiro ZIP
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                header('Content-Length: ' . filesize($zipFilePath));

                readfile($zipFilePath);

                // Remover ficheiros temporários
                foreach ($convertedFiles as $convertedFile) {
                    unlink($convertedFile);
                }
                unlink($zipFilePath);
            }
        } else {
            http_response_code(400);
            echo 'Erros encontrados: ' . implode(' ', $errors);
        }
    } else {
        http_response_code(400);
        echo 'Nenhum ficheiro foi enviado ou ocorreu um erro no upload.';
    }
} else {
    http_response_code(405);
    echo 'Método não permitido.';
}

return; // VErs\ão boa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['images']) && count($_FILES['images']['error']) > 0) {
        $sqlContent = "";
        $convertedFileName = "";
        $errors = [];

        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['images']['name'][$index]);
                $destination = 'uploads/' . $fileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    // Create the destination file
                    $convertedFileName = "$fileName.sql";
                    $convertedFilePath = 'uploads/' . $convertedFileName;

                    if (fopen($convertedFilePath, "w")) {
                        // Comando do script shell para converter a imagem
                        $command = escapeshellcmd("./fileToBase64ClobOracle.sh $destination IBase64 $convertedFilePath");
                        $output = shell_exec($command);

                        // Verifica se o comando shell foi executado com sucesso
                        if (file_exists($convertedFilePath)) {
                            $sqlContent .= file_get_contents($convertedFilePath);
                        } else {
                            $errors[] = "Erro ao converter o ficheiro $fileName.";
                        }
                    } else {
                        $errors[] = "Não foi possível criar o ficheiro $convertedFileName.";
                    }
                } else {
                    $errors[] = "Erro ao mover o ficheiro $fileName para a pasta de uploads.";
                }
            } else {
                $errors[] = "Erro no upload do ficheiro $fileName.";
            }
        }

        if (!empty($sqlContent) && empty($errors)) {
            // Definindo o cabeçalho para download do ficheiro .sql
            header('Content-Disposition: attachment; filename="' . $convertedFileName . '"');
            header('Content-Type: application/sql');
            echo $sqlContent;
            unlink($convertedFilePath);
        } else {
            http_response_code(400);
            echo 'Erros encontrados: ' . implode(' ', $errors);
        }
    } else {
        http_response_code(400);
        echo 'Nenhum ficheiro foi enviado ou ocorreu um erro no upload.';
    }
} else {
    http_response_code(405);
    echo 'Método não permitido.';
}

return;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['images']) && count($_FILES['images']['error']) > 0) {
        $sqlContent = "";
        $convertedFileName = "";
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['images']['name'][$index];
                $destination = 'uploads/' . $fileName;
                move_uploaded_file($tmpName, $destination);

                // Create the destination file
                $convertedFileName = "$fileName.sql";
                fopen($convertedFileName, "w");


                // Comando do script shell para converter a imagem
                $command = escapeshellcmd("./fileToBase64ClobOracle.sh $destination IBase64 $convertedFileName");
                $output = shell_exec($command);

                // Adicionar o conteúdo convertido ao conteúdo do ficheiro .sql
                $sqlContent .= file_get_contents($convertedFileName);
            }
        }

        if (!empty($convertedFileName) && !empty($sqlContent)) {
            // Definindo o cabeçalho para download do ficheiro .sql
            header('Content-Disposition: attachment; filename="' . $convertedFileName . '"');
            header('Content-Type: application/sql');
            echo $sqlContent;
            unlink($convertedFileName);
        } else {
            http_response_code(400);
            echo 'Erro ao converter o ficheiro.';
        }

    } else {
        http_response_code(400);
        echo 'Erro no upload dos ficheiros.';
    }
} else {
    http_response_code(405);
    echo 'Método não permitido.';
}
?>
