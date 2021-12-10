<?php
// research migration display completed proposals
function cfd_research_migration_completed_proposals_all()
{
	$output = "";
	$query = db_select('research_migration_proposal');
	$query->fields('research_migration_proposal');
	$query->condition('approval_status', 3);
	$query->orderBy('actual_completion_date', 'DESC');
	//$query->condition('is_completed', 1);
	$result = $query->execute();
	
	//var_dump($research_migration_abstract);die;
	if ($result->rowCount() == 0)
	{
		$output .= "Work has been completed for the following research migration. We welcome your contributions." ."<hr>";

	} //$result->rowCount() == 0
	else
	{
		$output .= "Work has been completed for the following research migration. We welcome your contributions." . "<hr>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$proposal_id = $row->id;
			$query1 = db_select('research_migration_submitted_abstracts_file');
			$query1->fields('research_migration_submitted_abstracts_file');
			$query1->condition('file_approval_status', 1);
			$query1->condition('proposal_id', $proposal_id);
			$research_migration_files = $query1->execute();
			$research_migration_abstract = $research_migration_files->fetchObject();
			$solver_used = $row->solver_used;
			$project_title = l($row->project_title, "research-migration-project/research-migration-run/" . $row->id) . t("<br><strong>(Solver used: ") . $solver_used . t(")</strong>") ;
			$year = date("Y", $row->actual_completion_date);
			$preference_rows[] = array(
				$i,
				$project_title,
				//$solver_used,
				$row->contributor_name,
				$row->university,
				$year
			);
			$i++;
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'research migration Project',
			//'Solver used',
			'Contributor Name',
			'University/ Institute',
			'Year of Completion'
		);
		$output .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $output;
}
// research migration display in progress proposals
function cfd_research_migration_progress_all()
{
	$page_content = "";
	$query = db_select('research_migration_proposal');
	$query->fields('research_migration_proposal');
	$query->condition('approval_status', 1);
	$query->condition('is_completed', 0);
	$result = $query->execute();
	if ($result->rowCount() == 0)
	{
		$page_content .= "Work is in progress for the following research migration under research migration Project<hr>";
	} //$result->rowCount() == 0
	else
	{
		$page_content .= "Work is in progress for the following research migration under research migration Project<hr>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$approval_date = date("Y", $row->approval_date);
			$preference_rows[] = array(
				$i,
				$row->project_title,
				$row->contributor_name,
				$row->university,
				$approval_date
			);
			$i++;
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'research migration Project',
			'Contributor Name',
			'Institute/ University',
			'Year'
		);
		$page_content .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $page_content;
}
