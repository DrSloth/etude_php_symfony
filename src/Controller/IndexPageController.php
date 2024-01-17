<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class IndexPageController extends AbstractController
{
    #[Route('/time', name: 'add_time', methods: ["POST"])]
    public function add_time(): Response
    {
        $db = get_db_con();
        
        $start_time = mysqli_real_escape_string($db, $_POST['start_time']);
        $end_time = mysqli_real_escape_string($db, $_POST['end_time']);

        if(strcmp($start_time, $end_time) < 0) {
            $db->query("INSERT INTO Times(start_time, end_time) VALUES('$start_time', '$end_time')");
        }

        return $this->redirectToRoute('app_index_page');
    }

    #[Route('/', name: 'app_index_page')]
    public function index(): Response
    {
        $db = get_db_con();

        $res = $db->query("SELECT * FROM Times;");
        $times = $res->fetch_all(\MYSQLI_ASSOC);
        
        $sum_res = $db->query(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) FROM Times;"
        );
        $sum = $sum_res->fetch_row()[0];
        $sum_s = floor($sum / 60) . ':' . lpad($sum % 60, '0', 2);

        return $this->render('index_page/index.html.twig', [
            'controller_name' => 'IndexPageController',
            'times' => $times,
            'time_sum' => $sum_s
        ]);
    }

    #[Route('/update', name: 'update_time', methods: ["POST"])]
    public function update_time(): Response
    {
        $db = get_db_con();
        
        $start_time = mysqli_real_escape_string($db, $_POST['start_time']);
        $end_time = mysqli_real_escape_string($db, $_POST['end_time']);
        $id = intval($_POST['id']);

        if(strcmp($start_time, $end_time) < 0) {
            $db->query("UPDATE Times SET start_time = '$start_time', end_time = '$end_time' WHERE id = $id");
        }

        return $this->redirectToRoute('app_index_page');
    }

    #[Route('/delete/{id_s}', name: 'delete_time')]
    public function delete_time(string $id_s): Response
    {
        $id = intval($id_s);
        $db = get_db_con();
        $db->query("DELETE FROM Times WHERE id = $id");

        return $this->redirectToRoute('app_index_page');
    }

    #[Route('/day_overview', name: 'day_overview')]
    public function day_overview(): Response
    {
        $db = get_db_con();

        $day = mysqli_real_escape_string($db, $_GET['day']);
        
        $res = $db->query("SELECT id, start_time, end_time FROM Times WHERE DATE(start_time) = '$day';");
        $times = $res->fetch_all(\MYSQLI_ASSOC);
        
        $sum_res = $db->query(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) FROM Times 
                WHERE DATE(start_time) = '$day';"
        );
        $sum = $sum_res->fetch_row()[0];
        $sum_s = floor($sum / 60) . ':' . lpad($sum % 60, '0', 2);

        return $this->render('index_page/index.html.twig', [
            'controller_name' => 'IndexPageController',
            'times' => $times,
            'time_sum' => $sum_s
        ]);
    }

    #[Route('/month_overview', name: 'month_overview')]
    public function month_overview(): Response
    {
        $db = get_db_con();

        $month = mysqli_real_escape_string($db, $_GET['month'] . '-01');
        
        $res = $db->query(
            "SELECT id, start_time, end_time FROM Times 
                WHERE MONTH(start_time) = MONTH('$month') AND YEAR(start_time) = YEAR('$month');"
        );
        $times = $res->fetch_all(\MYSQLI_ASSOC);

        $sum_res = $db->query(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) FROM Times 
                WHERE MONTH(start_time) = MONTH('$month') AND YEAR(start_time) = YEAR('$month');"
        );
        $sum = $sum_res->fetch_row()[0];
        $sum_s = floor($sum / 60) . ':' . lpad($sum % 60, '0', 2);
        
        return $this->render('index_page/index.html.twig', [
            'controller_name' => 'IndexPageController',
            'times' => $times,
            'time_sum' => $sum_s
        ]);
    }

    #[Route('/csv', name: 'csv_export')]
    public function csv_export(): Response
    {
        $db = get_db_con();
        $res = $db->query("SELECT id, start_time, end_time FROM Times");
        $times = $res->fetch_all(\MYSQLI_ASSOC);

        $s = "id;start_time;end_time\n";

        foreach($times as $t) {
            $s .= "{$t['id']};\"{$t['start_time']}\";\"{$t['end_time']}\"\n";
        }

        $resp = new Response();
        $resp->headers->set('Content-Type', 'text/csv');
        $resp->headers->set('Content-Disposition', 'attachment; filename=export.csv');
        $resp->setContent($s);
        $resp->send();
        return $resp;
    }
}

function get_db_con() {
    return new \mysqli('localhost', 'hassanabu-jabir', 'user123', 'etude');
}

function lpad($s, $pad, $len) {
    while(strlen($s) < $len) {
        $s = $pad . $s;
    }
    return $s;
}

