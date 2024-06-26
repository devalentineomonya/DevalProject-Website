<?php
session_start(); // Start the session
include_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $getUserLevel = $conn->prepare("SELECT security_level FROM users WHERE user_id = :user_id");
    $getUserLevel->bindParam(':user_id', $_SESSION['current_user']);
    $getUserLevel->execute();
    $level = $getUserLevel->fetch(PDO::FETCH_ASSOC);

    if ($level['security_level'] == 1) {
        if (isset($_POST['projectId'])) {
            $projectId = $_POST['projectId'];

            try {
                // Retrieve the image filename and category ID from the database
                $sqlSelect = "SELECT image_name FROM projects WHERE project_id = :projectId";
                $stmtSelect = $conn->prepare($sqlSelect);
                $stmtSelect->bindParam(':projectId', $projectId, PDO::PARAM_INT);
                $stmtSelect->execute();
                $resultSelect = $stmtSelect->fetch(PDO::FETCH_ASSOC);

                // Delete the link between project and category
                $sqlDeleteLink = "DELETE FROM projectcategories WHERE project_id = :project_id";
                $stmtDeleteLink = $conn->prepare($sqlDeleteLink);
                $stmtDeleteLink->bindValue(':project_id', $projectId, PDO::PARAM_INT);
                $stmtDeleteLink->execute();

                // Check if the project has no more links to categories
                $sqlCheckLinks = "SELECT COUNT(*) FROM projectcategories WHERE project_id = :project_id";
                $stmtCheckLinks = $conn->prepare($sqlCheckLinks);
                $stmtCheckLinks->bindValue(':project_id', $projectId, PDO::PARAM_INT);
                $stmtCheckLinks->execute();
                $linkCount = $stmtCheckLinks->fetchColumn();

                if ($linkCount == 0) {
                    // No more links, delete the project
                    $sqlDeleteProject = "DELETE FROM projects WHERE project_id = :projectId";
                    $stmtDeleteProject = $conn->prepare($sqlDeleteProject);
                    $stmtDeleteProject->bindParam(':projectId', $projectId, PDO::PARAM_INT);
                    $stmtDeleteProject->execute();

                    // Delete the image file if it exists
                    if ($resultSelect) {
                        $imageFilename = $resultSelect['image_name'];
                        $imagePath = '../images/' . $imageFilename;

                        if ($imageFilename && file_exists($imagePath)) {
                            // Check file permissions before attempting to delete
                            if (is_writable($imagePath)) {
                                if (unlink($imagePath)) {
                                    echo "Image deleted successfully.";
                                } else {
                                    echo "Error deleting image.";
                                }
                            } else {
                                echo "No write permissions on the image file.";
                            }
                        } else {
                            echo "Image file not found.";
                        }
                    }
                }

                exit();
            } catch (PDOException $e) {
                // Handle database errors
                echo "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-octagon me-1"></i>
            You don\'t have permission to access this service.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}
