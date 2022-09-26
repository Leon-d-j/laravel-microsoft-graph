<?php

namespace Dcblogdev\MsGraph\AdminResources;

use Dcblogdev\MsGraph\Facades\MsGraphAdmin;

class CalendarEvents extends MsGraphAdmin
{
    private $userId;
    private $top;
    private $skip;

    public function userid($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function top($top)
    {
        $this->top = $top;
        return $this;
    }

    public function skip($skip)
    {
        $this->skip = $skip;
        return $this;
    }

    public function get($calendarId, $params = [])
    {
        if ($this->userId == null) {
            throw new Exception("userId is required.");
        }

        $top = request('top', $this->top);
        $skip = request('skip', $this->skip);

        if ($params == []) {
            $params = http_build_query([
                "\$orderby" => "start/dateTime",
                "\$top" => $top,
                "\$skip" => $skip,
                "\$count" => "true",
                "\$filter" => "start/dateTime ge '2022-01-24' and start/dateTime le '2022-01-31'",
                "\$select" => "id,start,end,showAs,subject,attendees,sensitivity",
            ]);
        } else {
            $params = http_build_query($params);
        }

        $events = MsGraphAdmin::get("users/$this->userId/calendars/$calendarId/events?$params");
        $data = MsGraphAdmin::getPagination($events, $top, $skip);

        return [
            'events' => $events,
            'total' => $data['total'],
            'top' => $data['top'],
            'skip' => $data['skip'],
        ];
    }
	public function delete($calendarId, $eventId){
        if ($this->userId == null) {
            throw new Exception("userId is required.");
        }

        $events = MsGraphAdmin::delete("users/$this->userId/events/$eventId");

        return $events;
    }
	public function update($eventId, $data){
        if ($this->userId == null) {
            throw new Exception("userId is required.");
        }

        $events = MsGraphAdmin::patch("users/$this->userId/events/$eventId", $data);

        return $events;
    }
	
	public function getview($calendarId, $params = [])
    {
        if ($this->userId == null) {
            throw new Exception("userId is required.");
        }

        $top = request('top', $this->top);
        $skip = request('skip', $this->skip);

        if ($params == []) {
            $params = http_build_query([
                "\$orderby" => $params["startDateTime"],
                "\$top" => $top,
                "\$skip" => $skip,
                "startDateTime" => $params["startDateTime"],
                "endDateTime" => $params["endDateTime"],
            ]);
        } else {
            $params = http_build_query($params);
        }

        //$events = MsGraphAdmin::get("users/$this->userId/calendars/$calendarId/calendarView?startDateTime=2022-01-21T19:00:00-23:00&endDateTime=2022-02-25T19:00:00-23:00&\$top=100");
        $events = MsGraphAdmin::get("users/$this->userId/calendars/$calendarId/calendarView?$params");
        $data = MsGraphAdmin::getPagination($events, $top, $skip);

        return [
            'events' => $events,
            'total' => $data['total'],
            'top' => $data['top'],
            'skip' => $data['skip'],
        ];
    }

    public function find($calendarId, $eventId)
    {
        if ($this->userId == null) {
            throw new Exception("userId is required.");
        }

        return MsGraphAdmin::get("users/$this->userId/calendars/$calendarId/events/$eventId");
    }
	

    public function store($calendarId, $data)
    {
        if ($this->userId == null) {
            throw new Exception("userId is required.");
        }

        return MsGraphAdmin::post("users/$this->userId/calendars/$calendarId/events", $data);
    }
}
