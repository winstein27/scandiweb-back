<?php

use App\Core\Controller;

class Products extends Controller
{
    public function index()
    {
        $productModel = $this->createModel("Product");

        $products = $productModel->getAll();
        if (!$products) {
            http_response_code(204);
            exit;
        }

        echo json_encode($products, JSON_UNESCAPED_UNICODE);
    }

    public function store()
    {
        $body = $this->getRequestBody();

        $bodyArr = (array) $body;
        $errors = $this->getAddProductValidationErrors($bodyArr);

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode((["errors" => $errors]));
            return;
        }

        $modelByType = $this->createModel($body->type);

        $productBySku = $modelByType->findBySku($body->sku);

        if ($productBySku) {
            http_response_code(422);
            echo json_encode(["error" => "Product sku already registered.", "product" => $productBySku]);
            return;
        }

        $modelByType->setSku($body->sku);
        $modelByType->setName($body->name);
        $modelByType->setPrice(floatval($body->price));
        $modelByType->setType($body->type);
        $modelByType->setAttributes($body);

        $addedProductId = $modelByType->addProduct();

        if ($modelByType) {
            http_response_code(201);
            echo json_encode(["productId" => $addedProductId]);
            return;
        };
    }

    public function deleteMany()
    {
        $body = $this->getRequestBody();
        $productModel = $this->createModel("Product");

        if (!$body || !property_exists($body, "ids") || empty($body->ids)) {
            http_response_code(400);
            echo json_encode(["error" => "IDs are required."]);
            return;
        }

        $productModel->massDelete($body->ids);

        http_response_code(202);
        echo json_encode(["success" => "Products deleted."]);
    }

    private function getAddProductValidationErrors($data)
    {
        $errors = [];

        if (empty($data["sku"])) {
            $errors[] = "SKU is required.";
        }

        if (empty($data["name"])) {
            $errors[] = "Name is required.";
        }

        if (empty($data["price"])) {
            $errors[] = "Price is required.";
        } else {
            if (filter_var($data["price"], FILTER_VALIDATE_FLOAT) == false) {
                $errors[] = "Price must be a number.";
            }
        }

        if (empty($data["type"])) {
            $errors[] = "Type is required.";
        } else {
            if ($data["type"] == "book") {
                if (empty($data["weight"])) {
                    $errors[] = "Weight is required for books.";
                } else if (filter_var($data["weight"], FILTER_VALIDATE_FLOAT) == false) {
                    $errors[] = "Weight must be a number.";
                }
            }

            if ($data["type"] == "dvd") {
                if (empty($data["size"])) {
                    $errors[] = "Size is required for DVDs.";
                } else if (filter_var($data["size"], FILTER_VALIDATE_FLOAT) == false) {
                    $errors[] = "Size must be a number.";
                }
            }

            if ($data["type"] == "furniture") {
                if (empty($data["height"])) {
                    $errors[] = "Height is required for fornitures.";
                } else if (filter_var($data["height"], FILTER_VALIDATE_FLOAT) == false) {
                    $errors[] = "Height must be a number.";
                }

                if (empty($data["width"])) {
                    $errors[] = "Width is required for fornitures.";
                } else if (filter_var($data["width"], FILTER_VALIDATE_FLOAT) == false) {
                    $errors[] = "Width must be a number.";
                }

                if (empty($data["length"])) {
                    $errors[] = "Length is required for fornitures.";
                } else if (filter_var($data["length"], FILTER_VALIDATE_FLOAT) == false) {
                    $errors[] = "Length must be a number.";
                }
            }
        }

        return $errors;
    }
}
