<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Manage_booking_model extends CI_Model
{
    public function guides($booking)
    {
        $this->load->model("Tour_model");
        $guides = $this->Tour_model->get_tour_guides(
            (int) $booking["book_tour_id"],
        );
        $available = [];
        foreach ($guides as $guide) {
            if (
                !empty($booking["book_lang_id"]) &&
                !in_array(
                    (int) $booking["book_lang_id"],
                    array_map(
                        "intval",
                        array_column($guide["booking_languages"], "lang_id"),
                    ),
                    true,
                )
            ) {
                continue;
            }
            $rows = $this->db
                ->where("avail_tour_guide_id", $guide["tour_guide_id"])
                ->where("avail_date", $booking["book_date"])
                ->get("tour_guide_availability")
                ->result_array();
            $slot = false;
            $busy = false;
            foreach ($rows as $row) {
                $owned =
                    (int) $row["avail_book_id"] === (int) $booking["book_id"];
                if (
                    !$owned &&
                    ((int) $row["avail_book_id"] > 0 ||
                        in_array(
                            $row["avail_book_status"],
                            ["Reserved", "On-hold"],
                            true,
                        ))
                ) {
                    $busy = true;
                }
                if (
                    (int) $row["avail_slot_id"] ===
                        (int) $booking["book_slot_id"] &&
                    $row["avail_status"] === "Enable" &&
                    ($row["avail_book_status"] === "Available" || $owned)
                ) {
                    $slot = true;
                }
            }
            if ($slot && !$busy) {
                $guide["tour_guide_email"] = $this->db
                    ->select("tour_guide_email")
                    ->where("tour_guide_id", $guide["tour_guide_id"])
                    ->get("tour_guides")
                    ->row()->tour_guide_email;
                $available[] = $guide;
            }
        }
        return $available;
    }

    public function changeGuide($id, $guideId)
    {
        $this->db->trans_begin();
        $booking = $this->db
            ->query(
                "SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE",
                [$id],
            )
            ->row_array();
        if (
            !$booking ||
            !in_array($booking["book_status"], ["Pending", "Completed"], true)
        ) {
            $this->db->trans_rollback();
            return false;
        }
        $ids = array_unique(
            array_filter([(int) $booking["book_tour_guide_id"], $guideId]),
        );
        sort($ids);
        foreach ($ids as $lockId) {
            $this->db->query(
                "SELECT tour_guide_id FROM tour_guides WHERE tour_guide_id = ? FOR UPDATE",
                [$lockId],
            );
        }
        $guide = null;
        foreach ($this->guides($booking) as $candidate) {
            if ((int) $candidate["tour_guide_id"] === $guideId) {
                $guide = $candidate;
            }
        }
        if (!$guide) {
            $this->db->trans_rollback();
            return false;
        }
        if ((int) $booking["book_tour_guide_id"] === $guideId) {
            $this->db->trans_commit();
            return true;
        }
        $now = date("Y-m-d H:i:s");
        // Release only rows owned by this booking, including its same-day blocked slots.
        $this->db
            ->where("avail_book_id", $id)
            ->update("tour_guide_availability", [
                "avail_book_id" => 0,
                "avail_book_status" => "Available",
                "avail_updated" => $now,
            ]);
        $slot = $this->db
            ->where("avail_tour_guide_id", $guideId)
            ->where("avail_date", $booking["book_date"])
            ->where("avail_slot_id", $booking["book_slot_id"])
            ->where("avail_status", "Enable")
            ->where("avail_book_status", "Available")
            ->get("tour_guide_availability")
            ->row_array();
        if (!$slot) {
            $this->db->trans_rollback();
            return false;
        }
        $pending = $booking["book_status"] === "Pending";
        $this->db
            ->where("avail_tour_guide_id", $guideId)
            ->where("avail_date", $booking["book_date"]);
        if ($pending) {
            $this->db->where("avail_book_status", "Available");
        }
        $this->db->update("tour_guide_availability", [
            "avail_book_id" => $id,
            "avail_book_status" => $pending ? "On-hold" : "Unavailable",
            "avail_updated" => $now,
        ]);
        if (!$pending) {
            $this->db
                ->where("avail_id", $slot["avail_id"])
                ->update("tour_guide_availability", [
                    "avail_book_status" => "Reserved",
                ]);
        }
        $this->db->where("book_id", $id)->update("tour_bookings", [
            "book_tour_guide_id" => $guideId,
            "book_tour_guide_name" => $guide["tour_guide_name"],
            "book_avail_id" => $slot["avail_id"],
            "book_updated" => $now,
        ]);
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return false;
        }
        return $this->db->trans_commit();
    }
}
