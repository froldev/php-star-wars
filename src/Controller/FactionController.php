<?php

namespace App\Controller;

use App\Model\FactionManager;

/**
 * Class FactionController
 * @package Controller
 */
class FactionController extends AbstractController
{

  const FOLDER = 'faction';

  /**
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function list(): string
  {
    $factionsManager = new FactionManager();
    $factions = $factionsManager->selectFaction();

    return $this->twig->render('Faction/list.html.twig', [
      'factions' => $factions,
      'noPicture' => self::EMPTY_PICTURE,
      ]);
  }

    /**
   * @param int $id
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function details(int $id): string
  {
    $factionManager = new FactionManager();
    $faction = $factionManager->selectOneById($id);

    return $this->twig->render('Faction/details.html.twig', [
      'faction' => $faction,
      'noPicture' => self::EMPTY_PICTURE,
    ]);
  }

  /**
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function add(): string
  {
    $nameError = $pictureError = $fileDestination = null;
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $isValid = true;

      // download picture
      if (!empty($_FILES['new-picture']['name']) && isset($_FILES['new-picture'])) {
        $allowed = array('png', 'jpg', 'jpeg', 'gif');
        $fileExt = explode('.', $_FILES['new-picture']['name']);
        $fileExt = strtolower(end($fileExt));

        $fileNameNew = uniqid( self::FOLDER .'-', false) . '.' . $fileExt;
        $fileDestination = 'assets/images/'. self::FOLDER .'/' . $fileNameNew;

        $filename = substr($_FILES['new-picture']['name'], 1);

        if ($_FILES['new-picture']['size'] > 2097152) {
          $pictureError = "La photo ne doit pas dépasser 2 Mo";
          $isValid = false;
        }
        if(!in_array($fileExt, $allowed)) {
          $pictureError = "La photo doit être au format jpg, jpeg, gif ou png";
          $isValid = false;
        }

        if ($isValid) {
          if (move_uploaded_file($_FILES['new-picture']['tmp_name'], $fileDestination)) {
            $_POST['picture'] = "/".$fileDestination;
            if (file_exists($filename)) {
              unlink($filename);
            }
          } else {
            $pictureError = "Erreur durant l'importation de la photo";
            $isValid = false;
          }
        }
      }
      
      // save data
      if (empty($_POST['name']) || !isset($_POST['name'])) {
        $nameError = "Merci de saisir un nom de faction";
        $isValid = false;
      }
      if ($isValid) {
        if (empty($_POST['picture']) || !isset($_POST['picture'])) {
          $_POST["picture"] = self::EMPTY_PICTURE;
        }
        $factionManager = new FactionManager();
        $factionManager->insertFaction($_POST);
        $this->updateFolderPictures();
        header("Location:/faction/list");
      }
    }

    return $this->twig->render('Faction/add.html.twig', [
      'nameError'     => $nameError,
      'pictureError'  => $pictureError,
      'noPicture'     => self::EMPTY_PICTURE,
    ]);
  }

  /**
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function edit(int $id): string
  {
    $nameError = $pictureError = $fileDestination = null;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $isValid = true;
      
        // download picture
      if (!empty($_FILES['new-picture']['name']) && isset($_FILES['new-picture'])) {
        $allowed = array('png', 'jpg', 'jpeg', 'gif');
        $fileExt = explode('.', $_FILES['new-picture']['name']);
        $fileExt = strtolower(end($fileExt));

        $fileNameNew = uniqid( self::FOLDER .'-', false) . '.' . $fileExt;
        $fileDestination = 'assets/images/'. self::FOLDER .'/' . $fileNameNew;

        $filename = substr($_POST['picture'], 1);

        if ($_FILES['new-picture']['size'] > 2097152) {
          $pictureError = "La photo ne doit pas dépasser 2 Mo";
          $isValid = false;
        }
        if(!in_array($fileExt, $allowed)) {
          $pictureError = "La photo doit être au format jpg, jpeg, gif ou png";
          $isValid = false;
        }

        if ($isValid) {
          if (move_uploaded_file($_FILES['new-picture']['tmp_name'], $fileDestination)) {
            $_POST['picture'] = "/".$fileDestination;
            if (file_exists($filename)) {
              unlink($filename);
            }
          } else {
            $pictureError = "Erreur durant l'importation de la photo";
            $isValid = false;
          }
        }
      }
      
      // save data
      if (empty($_POST['name']) || !isset($_POST['name'])) {
        $nameError = "Merci de saisir un nom de faction";
        $isValid = false;
      }

      if ($isValid) {
        if (empty($_POST['picture']) || !isset($_POST['picture'])) {
          $_POST["picture"] = self::EMPTY_PICTURE;
        }
        $factionManager = new FactionManager();
        $factionManager->editFaction($_POST, $id);
        $this->updateFolderPictures();
        header('Location:/faction/list');
      }
    }

    $factionManager = new FactionManager();
    $faction = $factionManager->selectOneById($id);

    $pictureName = (str_replace('/assets/images/'. self::FOLDER .'/', '', $faction['picture']));

    return $this->twig->render('Faction/edit.html.twig', [
      'nameError'       => $nameError,
      'pictureError'    => $pictureError,
      'faction'         => $faction,
      'noPicture'       => self::EMPTY_PICTURE,
      'pictureName'     => $pictureName,
    ]);
  }

  /**
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function delete(int $id): void
  {
    $factionManager = new FactionManager();
    $faction = $factionManager->deleteFaction($id);
    $this->updateFolderPictures();
    header('Location: /faction/list/'.$id);
  }

  /**
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function updateFolderPictures(): void
  {
    $path = "/assets/images/". self::FOLDER ."/";
    $factionManager = new FactionManager(); // pictures in bdd
    $pictures = $factionManager->listOfFaction();
    foreach ($pictures as $key=>$value)
    {
      $pictureTable[$key] = trim(current(str_replace($path, " ", $value)));
    }
    $dir = substr($path, 1);; // pictures in folder
    $scan = array_diff(scandir($dir), array('..', '.'));

    $diff = array_diff($scan, $pictureTable); // differences
    foreach ($diff as $filename){ // delete pictures in folder but not in bdd
      if (file_exists(substr($path, 1).$filename)) {
        unlink(substr($path, 1).$filename);
      }
    }
  }
}
