# To add a new year to the undergrad catalogue

Follow these steps to get the site ready for adding courses to.

1. Disable the www destination on the catalogue site.
2. Edit the display name of the cat-admin/_admin/asset-factories/course-folder folder.
3. Edit the $lastyear and $nextyear in `addNextYearsCoursesFolders.php`, make sure the $start_asset is the ugrad folder, and run it.
4. Edit the $oldyear and $nextyear in `updateReferences.php` and run it.
5. Edit the years, $start_asset and set foldertest=true in `editAssetFactoryContainer.php` and run it.
6. Update the $catalogueIndexYear, $catalogueDiscPrinceYear, $FacultyPagesCurrentYear and $FacultyPagesFutureYear in the [_academic-year XSLT](https://cms.slc.edu:8443/entity/open.act?id=cc0aa4387f0000021c8ad4ac3f12f79b&type=format).
7. Edit the $academicYear in each of the catalogue/_reports XSLT, and make sure the publish-set is publishing hourly. Inform the content-creators.
8. Change the year in `createRelatedIDarray.php` and run it, ensuring that `relatedIDs.php` gets updated. *This script needs to be run whenever a discipline is added or renamed*.
9. Change the year in the `editAllCoursesMaintenance.php` script.

Cascade is now ready for the new years' courses to be added.

## Once the courses have been added

1. Archive the /catalogue/ folder on the server via FTP, re-enable the www destinations and publish each discipline folder!
2. Edit the $old and $new in `renameCoursesPageHeadlines.php` and run it. Edit the `undergradCoursesYear` variable in the [_academic-year XSLT](https://cms.slc.edu:8443/entity/open.act?id=cc0aa4387f0000021c8ad4ac3f12f79b&type=format&). Publish the Undergraduate site.
3. Edit the `$lastYear` in `moveLastYearsCoursesToArchived.php` and run it.
4. Edit `FacultyPagesCurrentYear` and `FacultyPagesFutureYear` in `_academic-year` and republish the faculty site (although doing so without the Grad Courses in place will make them empty, so republish with caution).
5. Turn off the reports publish-set, or at least make it only publish daily or weekly.

Note: If the course naming plugin stops work, `renamePagesBasedOnTitle.php` should help out, but it may not be perfect.

# To add a new year to the grad catalogue

*This documentation is fragile and requires updating. Use caution.*

1. Disable the www destination on the grad-catalogue site.
1. Change the year in the `editAllGradCourses.php` script.
1. Edit the $lastyear and $nextyear in `copyGradIndexPages.php`, and run it.
2. Make sure that the index/year pages are next to each other in the folder order, so the mobile nav neighbours show up.
3. Make sure the `$year` in `renameOldProgramLandingPages.php` is the old year and run it using the $start_asset `4e9e12a97f000001015d84e03ea3fb26` (the grad-catalogue base folder).
4. Change the $start_asset in `addNextYearsCoursesFolders.php` to be the grad folder, and run it.
5. Update the title of the grad-cat/index
6. Edit the $start_asset and set foldertest=false in `editAssetFactoryContainer.php` and run it.
7. Edit graduate-courses_ to use the included $academicYear (if you haven't already done so).

You might be able to see the grad courses on this site: https://my.slc.edu/ics/Academics/Course_Schedules/graduate.jnz

---

## Legacy:

Around 2013 we decided to maintain several years of courses on the website. This only lasted a year or two before we decided against it based on SEO. However, if it is ever decided to do this again the `copyIndexPages.php`, `renameOldProgramLandingPages.php` and `deletePreviousYearIndexPages.php` scripts may help out as a starting point, although these scripts were last modified before the catalogue was re-structured and it is doubtful that they will work—indeed, *they may have unpredictable results that have undesirable consequences*. The scripts and documentation are preserved merely as a starting point, and it might be easier to start from scratch anyway. If that is the case, these scripts should probably be archived, and this documentation removed.

1. If you want the previous years of catalogues, do the following:
    1. Edit the $lastyear and $nextyear in `copyIndexPages.php`, and run it.
    2. Make sure that the index/year pages are next to each other in the folder order, so the mobile nav neighbours show up (they should be last, unless new assets were created).
    3. Change the `$year` in `renameOldProgramLandingPages.php` to the old year and run it (make sure the $start_asset is `817373157f00000101f92de5bea1554a`).
    4. Make the newly-created humanities/languages/20xx-20xx not indexable.
    5. If you want to delete the previous year's index pages you can change the `$year_to_delete` in `deletePreviousYearIndexPages.php` and run it.