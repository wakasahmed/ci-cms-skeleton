<?php

class SiteModel extends SqlModel {


    public function __construct(){
        // Call the Model constructor
        parent::__construct();

    }

    public function getTourGuides($date="",$car="",$slot="",$tour_id="")
    {
        $data = array();
        if($date!="" && $car!="" && $slot!="" && $tour_id!="")
        {
            $date = date('Y-m-d', strtotime($date));
            $query = "SELECT 
				  *,
				  (SELECT 
					GROUP_CONCAT(lang_name) 
				  FROM
					tour_languages l
					INNER JOIN tour_guide_assigned_languages al
					  ON l.lang_id = al.lang_id 
				  WHERE l.lang_status = 'Enable' 
					AND al.tour_guide_id = d.tour_guide_id
				  ORDER BY l.lang_name) lang,
				  (SELECT 
					GROUP_CONCAT(lang_name_ar) 
				  FROM
					tour_languages l
					INNER JOIN tour_guide_assigned_languages al
					  ON l.lang_id = al.lang_id 
				  WHERE l.lang_status = 'Enable' 
					AND al.tour_guide_id = d.tour_guide_id
				  ORDER BY l.lang_name) lang_ar 
				FROM
				  tour_guide_availability a
				  INNER JOIN tour_guides d
					ON d.tour_guide_id = a.avail_tour_guide_id
				  INNER JOIN tour_slots s
					ON s.slot_id = a.avail_slot_id 
				  INNER JOIN tour_guide_assigned_tours t
				  ON t.tour_guide_id = d.tour_guide_id
				WHERE avail_date = '".$date."' 
				  AND avail_date >= '".date('Y-m-d')."'
				  AND avail_slot_id = ".$slot." 
				  AND t.tour_id = ".$tour_id."
				  
				  AND avail_book_id = 0
				  AND d.tour_guide_status = 'Enable'
				  AND s.slot_status = 'Enable' 
				ORDER BY RAND() ;
				
				";
                $data = $this->runQuery($query);
        }
        return $data;
    }

    public function getToursByDates($book_cin_date,$book_cout_date,$book_car,$book_slot,$book_tour,$book_lang)
    {
        $data = array();
            $book_cin_date = date('Y-m-d', strtotime($book_cin_date));
            $book_cout_date = date('Y-m-d', strtotime($book_cout_date));
            $book_car = $book_car;
            $book_slot = $book_slot;
            $book_tour = $book_tour;
            $book_lang = $book_lang;

            $sWH = "";
            if($book_slot!="")
            {
                $sWH = "	AND a.avail_slot_id  IN (".$this->db->escape_str($book_slot).") ";
            }
            $tWH = "";
            if($book_tour!="")
            {
                $tWH = "	AND t.tour_id IN (".$this->db->escape_str($book_tour).") ";
            }
            $aWH = "";
            $aINNER = "";
            if($book_lang!="")
            {
                $aINNER  = " INNER JOIN tour_guide_assigned_languages dal ON dal.tour_guide_id=d.tour_guide_id ";
                $aWH = "	AND dal.lang_id  IN (".$this->db->escape_str($book_lang).") ";
            }


            $query = "SELECT 
				  *, (SELECT 
					GROUP_CONCAT(lang_name) 
				  FROM
					tour_languages l
					INNER JOIN tour_guide_assigned_languages al
					  ON l.lang_id = al.lang_id 
				  WHERE l.lang_status = 'Enable' 
					AND al.tour_guide_id = d.tour_guide_id
				  ORDER BY l.lang_name) lang,
				  (SELECT 
					GROUP_CONCAT(lang_name_ar) 
				  FROM
					tour_languages l
					INNER JOIN tour_guide_assigned_languages al
					  ON l.lang_id = al.lang_id 
				  WHERE l.lang_status = 'Enable' 
					AND al.tour_guide_id = d.tour_guide_id
				  ORDER BY l.lang_name) lang_ar,
				  (SELECT 
					GROUP_CONCAT(tour_name) 
				  FROM
					tour_guide_assigned_tours dat
					INNER JOIN tours datt 
					  ON datt.tour_id = dat.tour_id 
				  WHERE datt.tour_status = 'Enable' 
					AND dat.tour_guide_id = d.tour_guide_id
				  ORDER BY datt.tour_order) tours,
				  (SELECT 
					GROUP_CONCAT(tour_name_ar) 
				  FROM
					tour_guide_assigned_tours dat
					INNER JOIN tours datt 
					  ON datt.tour_id = dat.tour_id 
				  WHERE datt.tour_status = 'Enable' 
					AND dat.tour_guide_id = d.tour_guide_id
				  ORDER BY datt.tour_order) tours_ar  
				FROM
				  tour_guide_availability a
				  INNER JOIN tour_guides d
					ON d.tour_guide_id = a.avail_tour_guide_id
				  INNER JOIN tour_slots s
					ON s.slot_id = a.avail_slot_id 
				  INNER JOIN tour_guide_assigned_tours t
				  ON t.tour_guide_id = d.tour_guide_id
				   ".$aINNER." 
				  INNER JOIN tours tt
				  ON tt.tour_id = t.tour_id
				WHERE (avail_date BETWEEN '".$this->db->escape_str($book_cin_date)."' AND '".$this->db->escape_str($book_cout_date)."') AND avail_date >= '".date('Y-m-d')."' ".$sWH.$tWH.$aWH."
				  AND avail_book_id = 0
				  AND d.tour_guide_status = 'Enable'
				  AND s.slot_status = 'Enable'
				  AND tt.tour_status= 'Enable' 
				GROUP BY d.tour_guide_id
				ORDER BY avail_date ASC 
				
				";

                  //AND avail_slot_id = ".$slot."
                  //AND t.tour_id = ".$tour_id."

                $data = $this->runQuery($query);
                if(!empty($data)&&count($data)>0)
                {
                    foreach($data as $k => $d)
                    {
                        $q = "SELECT 
							  a.*,s.slot_name,s.slot_time,s.slot_id,s.slot_name_ar 
							FROM
							  tour_guide_availability a
							  INNER JOIN tour_guides d
								ON d.tour_guide_id = a.avail_tour_guide_id
							  INNER JOIN tour_slots s
								ON s.slot_id = a.avail_slot_id 
							WHERE (avail_date BETWEEN '".$this->db->escape_str($book_cin_date)."' AND '".$this->db->escape_str($book_cout_date)."') AND avail_date >= '".date('Y-m-d')."'
							  AND avail_tour_guide_id = ".$d['tour_guide_id']."
							  AND avail_book_id = 0
							  AND d.tour_guide_status = 'Enable'
							  AND s.slot_status = 'Enable' 
							ORDER BY avail_date ASC ";
                        $data[$k]['slots'] = $this->runQuery($q);

                        if(!empty($data[$k]['slots']))
                        {
                            foreach($data[$k]['slots'] as $kk=>$s)
                            {
                                $time1 = strtotime(date('M d, Y H:i:s'));
                                $time2 = strtotime(date('M d, Y H:i:s',strtotime($s['avail_date'].' '.$s['slot_time'])));
                                $diff = round(($time2 - $time1) / ( 60 * 60 ));

                                if($diff<BOOK_HOUR_LIMIT)
                                {
                                    unset($data[$k]['slots'][$kk]);
                                }
                            }
                        }
                        if(empty($data[$k]['slots']))
                        {
                            unset($data[$k]);
                        }
                    }
                }

        return $data;
    }

    public function getDatesByAttr(
        $tourID,
        $langID,
        $carID,
        $cinDate,
        $coutDate,
        $bookHourLimit,
        $selfBooking
    )
    {
        $data = array();
        $langQuery = "SELECT 
count(*) cnt
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_languages l
  ON l.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_tours t
  ON t.tour_guide_id = d.tour_guide_id
WHERE (
    avail_date BETWEEN '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
    AND '".$this->db->escape_str(date('Y-m-d',strtotime($coutDate)))."'
  ) 
  AND avail_book_id = 0 
  AND d.tour_guide_status = 'Enable'
  AND s.slot_status = 'Enable'
  AND t.tour_id = ".$this->db->escape_str($tourID)."
ORDER BY avail_date ASC ";
$carQuery = "SELECT 
count(*) cnt
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_languages l
  ON l.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_tours t
  ON t.tour_guide_id = d.tour_guide_id
WHERE (
    avail_date BETWEEN '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
    AND '".$this->db->escape_str(date('Y-m-d',strtotime($coutDate)))."'
  ) 
  AND avail_book_id = 0 
  AND d.tour_guide_status = 'Enable'
  AND s.slot_status = 'Enable'
  AND l.lang_id = ".$this->db->escape_str($langID)."
  AND t.tour_id = ".$this->db->escape_str($tourID)."
ORDER BY avail_date ASC ";
$tourQuery = "SELECT 
count(*) cnt
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_languages l
  ON l.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_tours t
  ON t.tour_guide_id = d.tour_guide_id
WHERE (
    avail_date BETWEEN '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
    AND '".$this->db->escape_str(date('Y-m-d',strtotime($coutDate)))."'
  ) 
  AND avail_book_id = 0 
  AND d.tour_guide_status = 'Enable'
  AND s.slot_status = 'Enable'
  AND l.lang_id = ".$this->db->escape_str($langID)."
ORDER BY avail_date ASC ";

        $langData = $this->runQuery($langQuery,1);
        $data['lang'] = (int) $langData['cnt'];

        $carData = $this->runQuery($carQuery,1);
        $data['car'] = (int) $carData['cnt'];

        $tourData = $this->runQuery($tourQuery,1);
        $data['tour'] = (int) $tourData['cnt'];

        if($selfBooking=="No")
        {
            $dateQuery = "SELECT 
						a.*,s.slot_name,slot_time,s.slot_id,s.slot_name_ar
						FROM
						  tour_guide_availability a
						  INNER JOIN tour_guides d
							ON d.tour_guide_id = a.avail_tour_guide_id
						  INNER JOIN tour_slots s
							ON s.slot_id = a.avail_slot_id 
						  INNER JOIN tour_guide_assigned_languages l
						  ON l.tour_guide_id = d.tour_guide_id
						  INNER JOIN tour_guide_assigned_tours t
						  ON t.tour_guide_id = d.tour_guide_id
						WHERE (
							avail_date BETWEEN '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
							AND '".$this->db->escape_str(date('Y-m-d',strtotime($coutDate)))."'
						  ) 
						  AND avail_book_id = 0 
						  AND d.tour_guide_status = 'Enable'
						  AND s.slot_status = 'Enable'
						  AND l.lang_id = ".$this->db->escape_str($langID)."
						  AND t.tour_id = ".$this->db->escape_str($tourID)."
						  GROUP BY avail_date, avail_slot_id
						ORDER BY avail_date ASC ";
        }else{
            $dateQuery = "SELECT 
						a.*,s.slot_name,slot_time,s.slot_id,s.slot_name_ar,tour_guide_name,tour_guide_name_ar
						FROM
						  tour_guide_availability a
						  INNER JOIN tour_guides d
							ON d.tour_guide_id = a.avail_tour_guide_id
						  INNER JOIN tour_slots s
							ON s.slot_id = a.avail_slot_id 
						  INNER JOIN tour_guide_assigned_languages l
						  ON l.tour_guide_id = d.tour_guide_id
						  INNER JOIN tour_guide_assigned_tours t
						  ON t.tour_guide_id = d.tour_guide_id
						WHERE (
							avail_date BETWEEN '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
							AND '".$this->db->escape_str(date('Y-m-d',strtotime($coutDate)))."'
						  ) 
						  AND avail_book_id = 0 
						  AND d.tour_guide_status = 'Enable'
						  AND s.slot_status = 'Enable'
						  AND l.lang_id = ".$this->db->escape_str($langID)."
						  AND t.tour_id = ".$this->db->escape_str($tourID)."
						 
						ORDER BY avail_date ASC ";
        }



        $data['avail_dates']= '<option value="">'.(($this->langPrefix=="") ? 'Select Booking Date'  : 'حدد تاريخ الحجز').'</option>';
        $dates = $this->runQuery($dateQuery);
        $data['total_records'] = count($dates);
        $months = array(
            "Jan" => "يناير",
            "Feb" => "فبراير",
            "Mar" => "مارس",
            "Apr" => "أبريل",
            "May" => "مايو",
            "Jun" => "يونيو",
            "Jul" => "يوليو",
            "Aug" => "أغسطس",
            "Sep" => "سبتمبر",
            "Oct" => "أكتوبر",
            "Nov" => "نوفمبر",
            "Dec" => "ديسمبر"
        );
        $days = array(
            'Monday' 	=> 'الإثنين',
            'Tuesday' 	=> 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' 	=> 'الخميس',
            'Friday' 	=> 'الجمعة',
            'Saturday' 	=> 'السبت',
            'Sunday' 	=> 'الأحد'
        );
        $ampm = array(
            'am' => 'صباحاً',
            'pm' => 'مساءاً'
        );

        if(!empty($dates))
        {
            foreach($dates as $k=>$d)
            {
                $time1 = strtotime(date('M d, Y H:i:s'));
                $time2 = strtotime(date('M d, Y H:i:s',strtotime($d['avail_date'].' '.$d['slot_time'])));
                $diff = round(($time2 - $time1) / ( 60 * 60 ));
                if($diff>=$bookHourLimit)
                {
                    if($selfBooking=="No")
                    {
                        $data['avail_dates'] .= '<option value="'.$d['avail_date'].'||'.$d['avail_slot_id'].'">';
                        if($this->langPrefix=="")
                        {
                            $data['avail_dates'] .= date('l, d F Y', strtotime($d['avail_date'])).' - '.$d['slot_name'.$this->langPrefix];
                        }else{
                            $data['avail_dates'] .= $days[date('l',strtotime($d['avail_date']))].' ,'.date('d', strtotime($d['avail_date'])).' '.$months[date('M', strtotime($d['avail_date']))].' '.date('Y', strtotime($d['avail_date'])).' - '.$d['slot_name'.$this->langPrefix];
                        }
                    }else{
                        $data['avail_dates'] .= '<option value="'.$d['avail_id'].'">';
                        if($this->langPrefix=="")
                        {
                            $data['avail_dates'] .= $d['tour_guide_name'].' - '.date('l, d F Y', strtotime($d['avail_date'])).' - '.$d['slot_name'.$this->langPrefix];
                        }else{
                            $data['avail_dates'] .= $d['tour_guide_name_ar'].' - '.$days[date('l',strtotime($d['avail_date']))].' ,'.date('d', strtotime($d['avail_date'])).' '.$months[date('M', strtotime($d['avail_date']))].' '.date('Y', strtotime($d['avail_date'])).' - '.$d['slot_name'.$this->langPrefix];
                        }
                    }
                    $data['avail_dates'] .= '</option>';
                }else{
                    unset($dates[$k]);
                }
            }
        }
        $data['total_records'] = count($dates);
        return $data;
    }

    public function getDatesByAttrSingeDate(
        $tourID,
        $langID,
        $carID,
        $cinDate,
        $bookHourLimit,
        $selfBooking
    )
    {
        $data = array();
        $langQuery = "SELECT 
count(*) cnt
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_languages l
  ON l.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_tours t
  ON t.tour_guide_id = d.tour_guide_id
WHERE 
  avail_date = '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
  AND avail_book_id = 0 
  AND d.tour_guide_status = 'Enable'
  AND s.slot_status = 'Enable'
  AND t.tour_id = ".$this->db->escape_str($tourID)."
ORDER BY avail_date ASC ";
$carQuery = "SELECT 
count(*) cnt
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_languages l
  ON l.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_tours t
  ON t.tour_guide_id = d.tour_guide_id
WHERE 
  avail_date = '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
  AND avail_book_id = 0 
  AND d.tour_guide_status = 'Enable'
  AND s.slot_status = 'Enable'
  AND l.lang_id = ".$this->db->escape_str($langID)."
  AND t.tour_id = ".$this->db->escape_str($tourID)."
ORDER BY avail_date ASC ";
$tourQuery = "SELECT 
count(*) cnt
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_languages l
  ON l.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_tours t
  ON t.tour_guide_id = d.tour_guide_id
WHERE 
  avail_date = '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."'
  AND avail_book_id = 0 
  AND d.tour_guide_status = 'Enable'
  AND s.slot_status = 'Enable'
  AND l.lang_id = ".$this->db->escape_str($langID)."
ORDER BY avail_date ASC ";

        $langData = $this->runQuery($langQuery,1);
        $data['lang'] = (int) $langData['cnt'];

        $carData = $this->runQuery($carQuery,1);
        $data['car'] = (int) $carData['cnt'];

        $tourData = $this->runQuery($tourQuery,1);
        $data['tour'] = (int) $tourData['cnt'];

        if($selfBooking=="No")
        {
            $dateQuery = "SELECT 
						a.*,s.slot_name,slot_time,s.slot_id,s.slot_name_ar
						FROM
						  tour_guide_availability a
						  INNER JOIN tour_guides d
							ON d.tour_guide_id = a.avail_tour_guide_id
						  INNER JOIN tour_slots s
							ON s.slot_id = a.avail_slot_id 
						  INNER JOIN tour_guide_assigned_languages l
						  ON l.tour_guide_id = d.tour_guide_id
						  INNER JOIN tour_guide_assigned_tours t
						  ON t.tour_guide_id = d.tour_guide_id
						WHERE
						  avail_date = '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
						  AND avail_book_id = 0 
						  AND d.tour_guide_status = 'Enable'
						  AND s.slot_status = 'Enable'
						  AND l.lang_id = ".$this->db->escape_str($langID)."
						  AND t.tour_id = ".$this->db->escape_str($tourID)."
						  GROUP BY avail_date, avail_slot_id
						ORDER BY slot_order ASC ";
        }else{
            $dateQuery = "SELECT 
						a.*,s.slot_name,slot_time,s.slot_id,s.slot_name_ar,tour_guide_name,tour_guide_name_ar
						FROM
						  tour_guide_availability a
						  INNER JOIN tour_guides d
							ON d.tour_guide_id = a.avail_tour_guide_id
						  INNER JOIN tour_slots s
							ON s.slot_id = a.avail_slot_id 
						  INNER JOIN tour_guide_assigned_languages l
						  ON l.tour_guide_id = d.tour_guide_id
						  INNER JOIN tour_guide_assigned_tours t
						  ON t.tour_guide_id = d.tour_guide_id
						WHERE
						  avail_date = '".$this->db->escape_str(date('Y-m-d',strtotime($cinDate)))."' 
						  AND avail_book_id = 0 
						  AND d.tour_guide_status = 'Enable'
						  AND s.slot_status = 'Enable'
						  AND l.lang_id = ".$this->db->escape_str($langID)."
						  AND t.tour_id = ".$this->db->escape_str($tourID)."
						 
						ORDER BY slot_order ASC ";
        }



        $data['avail_dates']= '<option value="">'.(($this->langPrefix=="") ? 'Select Visiting Slot'  : 'اختر أوقات الزيارة').'</option>';
        $dates = $this->runQuery($dateQuery);
        $data['total_records'] = count($dates);
        $months = array(
            "Jan" => "يناير",
            "Feb" => "فبراير",
            "Mar" => "مارس",
            "Apr" => "أبريل",
            "May" => "مايو",
            "Jun" => "يونيو",
            "Jul" => "يوليو",
            "Aug" => "أغسطس",
            "Sep" => "سبتمبر",
            "Oct" => "أكتوبر",
            "Nov" => "نوفمبر",
            "Dec" => "ديسمبر"
        );
        $days = array(
            'Monday' 	=> 'الإثنين',
            'Tuesday' 	=> 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' 	=> 'الخميس',
            'Friday' 	=> 'الجمعة',
            'Saturday' 	=> 'السبت',
            'Sunday' 	=> 'الأحد'
        );
        $ampm = array(
            'am' => 'صباحاً',
            'pm' => 'مساءاً'
        );

        $bookDate = "";
        if(!empty($dates))
        {
            foreach($dates as $k=>$d)
            {
                $time1 = strtotime(date('M d, Y H:i:s'));
                $time2 = strtotime(date('M d, Y H:i:s',strtotime($d['avail_date'].' '.$d['slot_time'])));
                $diff = round(($time2 - $time1) / ( 60 * 60 ));
                if($diff>=$bookHourLimit)
                {
                    if($selfBooking=="No")
                    {
                        $data['avail_dates'] .= '<option value="'.$d['avail_date'].'||'.$d['avail_slot_id'].'">';
                        if($this->langPrefix=="")
                        {
                            $bookDate = date('l, d F Y', strtotime($d['avail_date']));
                            $data['avail_dates'] .= $d['slot_name'.$this->langPrefix];
                        }else{
                            $bookDate = $days[date('l',strtotime($d['avail_date']))].' ,'.date('d', strtotime($d['avail_date'])).' '.$months[date('M', strtotime($d['avail_date']))].' '.date('Y', strtotime($d['avail_date']));
                            $data['avail_dates'] .= $d['slot_name'.$this->langPrefix];
                        }
                    }else{
                        $data['avail_dates'] .= '<option value="'.$d['avail_id'].'">';
                        if($this->langPrefix=="")
                        {
                            $bookDate = date('l, d F Y', strtotime($d['avail_date']));
                            $data['avail_dates'] .= $d['tour_guide_name'].' - '.$d['slot_name'.$this->langPrefix];
                        }else{
                            $bookDate = $days[date('l',strtotime($d['avail_date']))].' ,'.date('d', strtotime($d['avail_date'])).' '.$months[date('M', strtotime($d['avail_date']))].' '.date('Y', strtotime($d['avail_date']));
                            $data['avail_dates'] .= $d['tour_guide_name_ar'].' - '.$d['slot_name'.$this->langPrefix];
                        }
                    }
                    $data['avail_dates'] .= '</option>';
                }else{
                    unset($dates[$k]);
                }
            }
        }
        $data['bookDate'] = $bookDate;
        $data['total_records'] = count($dates);
        return $data;
    }


    public function getAvailDateByFCFS($book_date,$slot_id,$tour_id,$vehicle_id,$lang_id)
    {
        $query = "SELECT 
  a.avail_id,
  (SELECT 
    book_added 
  FROM
    tour_bookings b 
  WHERE b.book_tour_guide_id = d.tour_guide_id
    AND b.book_vehicle_id = ".$vehicle_id."
    AND b.book_avail_id > 0 
  ORDER BY book_id DESC 
  LIMIT 1) last_booking 
FROM
  tour_guide_availability a
  INNER JOIN tour_guides d
    ON d.tour_guide_id = a.avail_tour_guide_id
  INNER JOIN tour_slots s
    ON s.slot_id = a.avail_slot_id 
  INNER JOIN tour_guide_assigned_tours t
    ON t.tour_guide_id = d.tour_guide_id
  INNER JOIN tour_guide_assigned_languages l
    ON l.tour_guide_id = d.tour_guide_id
WHERE a.avail_book_id = 0 
  AND a.avail_date = '".$book_date."' 
  AND a.avail_slot_id = ".$slot_id." 
  AND t.tour_id = ".$tour_id." 
  AND l.lang_id = ".$lang_id."
  AND s.slot_status = 'Enable'
  AND d.tour_guide_status = 'Enable' ";

        $dates = $this->runQuery($query);
        $availID = 0;
        $stamp = 0;

        if(!empty($dates)&&count($dates)>0)
        {
            $availID 	= $dates[0]['avail_id'];
            $stamp 		= $dates[0]['last_booking'] = ($dates[0]['last_booking'] == "") ? 0 : strtotime($dates[0]['last_booking']);
            foreach($dates as $d)
            {
                $d['last_booking'] = ($d['last_booking'] == "") ? 0 : strtotime($d['last_booking']);
                if($stamp >= $d['last_booking'])
                {
                    $stamp = $d['last_booking'];
                    $availID = $d['avail_id'];
                }
            }
        }
        return $availID;
    }

    public function getDatesByTourGuideID($tourGuideID=0,$book_cin_date="0000-00-00",$book_cout_date="0000-00-00")
    {
        $data = array();
        $data['avail_dates']= '<option value="">'.(($this->langPrefix=="") ? 'Select Booking Date'  : 'حدد تاريخ الحجز').'</option>';
        $data['avail_tours'] = '<option value="">'.(($this->langPrefix=="") ? 'Select Tour'  : 'اختر جولة').'</option>';
        $data['avail_cars'] = '<option value="">'.(($this->langPrefix=="") ? 'Select Number of Guests'  : 'حدد عدد الضيوف').'</option>';
        $data['avail_lang'] = '<option value="">'.(($this->langPrefix=="") ? 'Select Preferred Language'  : 'اختر اللغة المفضلة').'</option>';


        /*DATES*/
        $dateQuery = "SELECT 
		  a.*,s.slot_name,slot_time,s.slot_id,s.slot_name_ar 
		FROM
		  tour_guide_availability a
		  INNER JOIN tour_guides d
			ON d.tour_guide_id = a.avail_tour_guide_id
		  INNER JOIN tour_slots s
			ON s.slot_id = a.avail_slot_id 
		WHERE (avail_date BETWEEN '".$this->db->escape_str($book_cin_date)."' AND '".$this->db->escape_str($book_cout_date)."')
		  AND avail_tour_guide_id = ".$tourGuideID."
		  AND avail_book_id = 0
		  AND d.tour_guide_status = 'Enable'
		  AND s.slot_status = 'Enable' 
		ORDER BY avail_date ASC ";
        $dates = $this->runQuery($dateQuery);
        $months = array(
            "Jan" => "يناير",
            "Feb" => "فبراير",
            "Mar" => "مارس",
            "Apr" => "أبريل",
            "May" => "مايو",
            "Jun" => "يونيو",
            "Jul" => "يوليو",
            "Aug" => "أغسطس",
            "Sep" => "سبتمبر",
            "Oct" => "أكتوبر",
            "Nov" => "نوفمبر",
            "Dec" => "ديسمبر"
        );
        $days = array(
            'Monday' 	=> 'الإثنين',
            'Tuesday' 	=> 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' 	=> 'الخميس',
            'Friday' 	=> 'الجمعة',
            'Saturday' 	=> 'السبت',
            'Sunday' 	=> 'الأحد'
        );
        $ampm = array(
            'am' => 'صباحاً',
            'pm' => 'مساءاً'
        );

        if(!empty($dates))
        {
            foreach($dates as $d)
            {
                $time1 = strtotime(date('M d, Y H:i:s'));
                $time2 = strtotime(date('M d, Y H:i:s',strtotime($d['avail_date'].' '.$d['slot_time'])));
                $diff = round(($time2 - $time1) / ( 60 * 60 ));
                if($diff>=BOOK_HOUR_LIMIT)
                {
                    $data['avail_dates'] .= '<option value="'.$d['avail_id'].'">';
                    if($this->langPrefix=="")
                    {
                        $data['avail_dates'] .= date('l, d F Y', strtotime($d['avail_date'])).' - '.$d['slot_name'.$this->langPrefix];
                    }else{
                        $data['avail_dates'] .= $days[date('l',strtotime($d['avail_date']))].' ,'.date('d', strtotime($d['avail_date'])).' '.$months[date('M', strtotime($d['avail_date']))].' '.date('Y', strtotime($d['avail_date'])).' - '.$d['slot_name'.$this->langPrefix];
                    }
                    $data['avail_dates'] .= '</option>';
                }
            }
        }

        /*TOURS*/
        $toursQuery = "
		SELECT t.tour_id,t.tour_name,t.tour_name_ar FROM tours t
		INNER JOIN tour_guide_assigned_tours dat
		ON t.tour_id = dat.tour_id
		INNER JOIN tour_guides d
		ON d.tour_guide_id = dat.tour_guide_id
		WHERE
		dat.tour_guide_id = ".$tourGuideID."
		AND t.tour_status = 'Enable'
		AND d.tour_guide_status = 'Enable'
		ORDER BY t.tour_order ASC	";
        $tours = $this->runQuery($toursQuery);
        if(!empty($tours))
        {
            foreach($tours as $d)
            {
                $data['avail_tours'] .= '<option value="'.$d['tour_id'].'">'.$d['tour_name'.$this->langPrefix].'</option>';
            }
        }

        /*CARS*/
        $carsQuery = "
		SELECT c.vehicle_id,c.vehicle_name,c.vehicle_name_ar,c.vehicle_price FROM vehicles c
		WHERE c.vehicle_status = 'Enable'
		ORDER BY c.vehicle_order ASC	";
        $cars = $this->runQuery($carsQuery);
        if(!empty($cars))
        {
            foreach($cars as $d)
            {

                $data['avail_cars'] .= '<option value="'.$d['vehicle_id'].'" data-price="'.(($this->langPrefix=="") ? 'SR' : '  ').$d['vehicle_price'].(($this->langPrefix=="") ? ' ' : ' ر.س ').'">'.$d['vehicle_name'.$this->langPrefix].'</option>';

            }
        }
        /*LANGUAGES*/
        $langQuery = "
		SELECT l.lang_id,l.lang_name,l.lang_name_ar FROM tour_languages l
		INNER JOIN tour_guide_assigned_languages dal
		ON l.lang_id = dal.lang_id
		INNER JOIN tour_guides d
		ON d.tour_guide_id = dal.tour_guide_id
		WHERE
		dal.tour_guide_id = ".$tourGuideID."
		AND l.lang_status = 'Enable'
		AND d.tour_guide_status = 'Enable'
		ORDER BY l.lang_order ASC	";
        $lang = $this->runQuery($langQuery);
        if(!empty($lang))
        {
            foreach($lang as $d)
            {
                $data['avail_lang'] .= '<option value="'.$d['lang_id'].'">'.$d['lang_name'.$this->langPrefix].'</option>';
            }
        }


        return $data;

    }

    public function getTours($limit=0)
    {
        $query = "SELECT *, (SELECT vehicle_guest_price FROM `vehicles` WHERE vehicle_status='Enable' ORDER BY vehicle_price ASC LIMIT 1) tour_guest_price,(SELECT vehicle_price FROM `vehicles` WHERE vehicle_status='Enable' ORDER BY vehicle_price ASC LIMIT 1) tour_price,(SELECT vehicle_id FROM `vehicles` WHERE vehicle_status='Enable' ORDER BY vehicle_price ASC LIMIT 1) vehicle_id,(SELECT vehicle_guests FROM `vehicles` WHERE vehicle_status='Enable' ORDER BY vehicle_price ASC LIMIT 1) tour_guests, (SELECT vehicle_guests_ar FROM `vehicles` WHERE vehicle_status='Enable' ORDER BY vehicle_price ASC LIMIT 1) tour_guests_ar FROM tours t WHERE t.tour_status='Enable' ORDER BY tour_order ASC ";
        if($limit>0)
        {
            $query.= " LIMIT ".$limit;
        }

        return $this->runQuery($query);



    }
}

?>
