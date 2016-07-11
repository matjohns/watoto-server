import _ from 'lodash'
import Moment from 'moment'

import React from 'react'
import Section from 'grommet/components/Section'
import Heading from 'grommet/components/Heading'
import Tiles from 'grommet/components/Tiles'
import Tile from 'grommet/components/Tile'
import Chart from 'grommet/components/Chart'
import Meter from 'grommet/components/Meter'

import Data from '../util/Data'

const Users = React.createClass({
  render() {
    if (!this.props.data) return null

    return (
      <Section>
        <Heading tag='h2' align='center'>
          Users
        </Heading>
        <Tiles fill={true}>
          <Tile>
            <Meter series={[
              {label: 'Active', value: this.props.data.users.active},
              {label: 'Installed', value: this.props.data.users.total - this.props.data.users.active},
            ]} max={this.props.data.users.total} type='circle' stacked={true} legend={true} />
          </Tile>
          <Tile>
            <Meter series={
              _.reduce(this.props.data.users.perVersion, (res, value, key) => {
                res.push({
                  label: key,
                  value: value,
                })
                return res
              }, [])
            } max={this.props.data.users.total} type='circle' stacked={true} legend={true} />
          </Tile>
        </Tiles>
        <Tiles fill={true}>
          <Tile>
            <Chart series={[
              {
                label: 'New',
                values: _.reduce(this.props.data.users.perDay, (res, value, key) => {
                  var day = Moment(key)
                  res.push([day.toDate(), value.new])
                  return res
                }, []),
              },
              {
                label: 'Existing',
                values: _.reduce(this.props.data.users.perDay, (res, value, key) => {
                  var day = Moment(key)
                  res.push([day.toDate(), value.existing])
                  return res
                }, []),
              },
            ]} type='area' smooth={true} xAxis={{
              placement: 'bottom',
              data: _.reduce(this.props.data.users.perDay, (res, value, key) => {
                var day = Moment(key)
                res.push({
                  label: day.format('D MMM'),
                  value: day.toDate(),
                })
                return res
              }, []),
            }} legend={{position: 'after', total: true}} a11yTitleId='dateSmoothChartTitle' a11yDescId='dateSmoothChartDesc' />
          </Tile>
        </Tiles>
        <Tiles fill={true}>
          <Tile>
            <Chart series={[
              {
                label: 'New',
                values: _.reduce(this.props.data.users.perWeek, (res, value, key) => {
                  var week = Moment(key, 'YYYY-W')
                  res.push([week.toDate(), value.new])
                  return res
                }, []),
              },
              {
                label: 'Existing',
                values: _.reduce(this.props.data.users.perWeek, (res, value, key) => {
                  var week = Moment(key, 'YYYY-W')
                  res.push([week.toDate(), value.existing])
                  return res
                }, []),
              },
            ]} type='area' smooth={true} xAxis={{
              placement: 'bottom',
              data: _.reduce(this.props.data.users.perWeek, (res, value, key) => {
                var week = Moment(key, 'YYYY-W')
                res.push({
                  label: week.format('[wk]W'),
                  value: week.toDate(),
                })
                return res
              }, []),
            }} legend={{position: 'after', total: true}} a11yTitleId='dateSmoothChartTitle' a11yDescId='dateSmoothChartDesc' />
          </Tile>
        </Tiles>
      </Section>
    )
  }
})

module.exports = Users
